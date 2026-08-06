<?php

use App\Models\Cppt;
use App\Models\DetailTindakan;
use App\Models\ExaminationType;
use App\Models\RadiologyImage;
use App\Models\ReportMasterTemplate;
use App\Models\Tindakan;
use App\Models\TindakanHasil;
use App\Models\User;
use App\Services\RadiologyAIService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public function rendering($view): void
    {
        $view->title('Pemeriksaan');
    }

    #[Locked]
    public Tindakan $tindakan;

    public string $activeTab = 'pemeriksaan';

    // State per baris: [detail_tindakan_id => ['template_id'=> 'checked'=> 'findings'=> 'impression'=> 'impression_source'=>]]
    public array $rows = [];
    public array $reviewResults = [];
    public $singleImageUpload;
    public ?int $uploadingDetailId = null;

    // Zoom & Delete Image
    public ?int $deletingImageId = null;
    public string $zoomedImageUrl = '';

    // Lightbox / image viewer (viewingDetailId digunakan oleh with() untuk load images)
    public int $viewingDetailId = 0;

    // Modal tambah baris baru
    public string $newExaminationTypeId = '';
    public ?int $deletingRowId = null;

    // Footer form
    public string $tglDatang = '';
    public string $tglValid = '';
    public string $tglPeriksa = '';
    public string $tglSelesai = '';
    public string $tglSerah = '';
    public string $dokterPembacaId = '';
    public string $operator1Id = '';
    public string $operator2Id = '';
    public string $operator3Id = '';
    public string $operator4Id = '';
    public string $operator5Id = '';
    public string $statusPemeriksaan = 'order';

    public function mount(Tindakan $tindakan): void
    {
        $this->authorize('view', $tindakan);

        $tindakan->load([
            'registrasi',
            'pelayanan.masterPoli',
            'patient',
            'dokterPembaca',
        ]);

        $this->tindakan = $tindakan;
        $this->dokterPembacaId = (string) $tindakan->dokter_pembaca_id;
        $this->operator1Id = (string) $tindakan->operator_1_id;
        $this->operator2Id = (string) $tindakan->operator_2_id;
        $this->operator3Id = (string) $tindakan->operator_3_id;
        $this->operator4Id = (string) $tindakan->operator_4_id;
        $this->operator5Id = (string) $tindakan->operator_5_id;
        $this->statusPemeriksaan = $tindakan->status;
        $this->tglDatang = $tindakan->tgl_datang?->format('Y-m-d\TH:i') ?? '';
        $this->tglValid = $tindakan->tgl_valid?->format('Y-m-d\TH:i') ?? '';
        $this->tglPeriksa = $tindakan->tgl_periksa?->format('Y-m-d\TH:i') ?? '';
        $this->tglSelesai = $tindakan->tgl_selesai?->format('Y-m-d\TH:i') ?? '';
        $this->tglSerah = $tindakan->tgl_serah?->format('Y-m-d\TH:i') ?? '';

        foreach ($tindakan->detailTindakan as $detail) {
            $this->rows[$detail->id] = [
                'template_id'       => '',
                'checked'           => (bool) $detail->is_examined,
                'findings'          => $detail->hasil->findings ?? '',
                'impression'        => $detail->hasil->impression ?? '',
                'impression_source' => $detail->hasil->impression_source ?? 'manual',
            ];
        }
    }

    public function applyTemplate(int $detailId): void
    {
        $templateId = $this->rows[$detailId]['template_id'] ?? null;
        if (! $templateId) return;

        $template = ReportMasterTemplate::find($templateId);
        if ($template) {
            $this->rows[$detailId]['findings'] = $template->uraian;
        }
    }

    public function generateImpression(int $detailId): void
    {
        $ai = app(RadiologyAIService::class);
        $detail = DetailTindakan::with('examinationType')->findOrFail($detailId);
        $findings = $this->rows[$detailId]['findings'] ?? '';

        if (blank($findings)) {
            Flux::toast(heading: 'Gagal', text: 'Isi Deskripsi (Findings) terlebih dahulu.', variant: 'danger');
            return;
        }

        $result = $ai->generateImpression($detail->examinationType->name, $this->tindakan->clinical_notes ?? '-', $findings);

        if (! $result) {
            Flux::toast(heading: 'Gagal', text: 'AI gagal menghasilkan Kesan. Cek log.', variant: 'danger');
            return;
        }

        $this->rows[$detailId]['impression'] = $result;
        $this->rows[$detailId]['impression_source'] = 'ai_generated';
        Flux::toast(heading: 'Berhasil', text: 'Kesan berhasil digenerate AI.', variant: 'success');
    }

    public function reviewReport(int $detailId): void
    {
        $ai = app(RadiologyAIService::class);
        $findings = $this->rows[$detailId]['findings'] ?? '';
        $impression = $this->rows[$detailId]['impression'] ?? '';

        if (blank($findings) || blank($impression)) {
            Flux::toast(heading: 'Gagal', text: 'Deskripsi dan Kesan harus terisi sebelum review.', variant: 'danger');
            return;
        }

        $detail = DetailTindakan::with('examinationType.reportTemplates')->findOrFail($detailId);
        $template = $detail->examinationType->reportTemplates()->where('is_default', true)->first();
        $sections = $template->template_content['sections'] ?? [];

        $missing = collect($sections)->filter(fn ($s) => stripos($findings, $s) === false)->values();
        $aiReview = $ai->reviewReport($findings, $impression);

        $this->reviewResults[$detailId] = [
            'completeness' => ['passed' => $missing->isEmpty(), 'missing' => $missing->toArray()],
            'ai_review'    => $aiReview,
        ];

        $this->dispatch('open-review-modal', detailId: $detailId);
    }

    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'rows.') && str_ends_with($property, '.impression')) {
            $parts = explode('.', $property);
            if (isset($parts[1])) {
                $detailId = (int) $parts[1];

                if (blank($value)) {
                    $this->rows[$detailId]['impression_source'] = 'manual';
                    return;
                }

                $currentSource = $this->rows[$detailId]['impression_source'] ?? 'manual';
                if ($currentSource === 'ai_generated') {
                    $this->rows[$detailId]['impression_source'] = 'ai_edited';
                }
            }
        }
    }

    /**
     * Hook Livewire yang mendeteksi perubahan pada property singleImageUpload.
     * Dipanggil secara otomatis ketika file selesai terunggah sepenuhnya ke server.
     */
    public function updatedSingleImageUpload($value): void
    {
        $detailId = $this->uploadingDetailId;
        if (! $detailId || ! $value) return;

        try {
            $this->validate([
                'singleImageUpload' => 'required|image|max:5120', // max 5MB
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'File tidak valid.';
            Flux::toast(heading: 'Upload Gagal', text: $msg, variant: 'danger');
            $this->singleImageUpload = null;
            $this->uploadingDetailId = null;
            return;
        }

        try {
            $path = $value->store("radiology/{$this->tindakan->id}", 'public');

            RadiologyImage::create([
                'detail_tindakan_id' => $detailId,
                'file_path'          => $path,
                'uploaded_by'        => Auth::id(),
            ]);

            $this->singleImageUpload = null;
            $this->uploadingDetailId = null;
            $this->tindakan->refresh();
            Flux::toast(heading: 'Berhasil', text: 'Gambar berhasil diunggah.', variant: 'success');
        } catch (\Throwable $e) {
            logger()->error('Upload gambar gagal: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Upload Gagal', text: 'Terjadi kesalahan saat menyimpan gambar.', variant: 'danger');
            $this->singleImageUpload = null;
            $this->uploadingDetailId = null;
        }
    }

    public function confirmDeleteImage(): void
    {
        if ($this->deletingImageId) {
            $image = RadiologyImage::find($this->deletingImageId);
            if ($image) {
                Storage::disk('public')->delete($image->file_path);
                $image->delete();
            }
            $this->deletingImageId = null;
            $this->tindakan->refresh();
            Flux::modal('delete-image-confirm')->close();
            Flux::toast(heading: 'Berhasil', text: 'Gambar berhasil dihapus.', variant: 'success');
        }
    }

    public function openImageModal(int $detailId): void
    {
        $this->viewingDetailId = $detailId;
        Flux::modal('image-lightbox')->show();
    }

    public function openAddRowModal(): void
    {
        $this->newExaminationTypeId = '';
        Flux::modal('add-row-modal')->show();
    }

    public function addRow(): void
    {
        if (! $this->newExaminationTypeId) {
            Flux::toast(heading: 'Perhatian', text: 'Pilih jenis pemeriksaan terlebih dahulu.', variant: 'warning');
            return;
        }

        $detail = DetailTindakan::create([
            'tindakan_id'         => $this->tindakan->id,
            'examination_type_id' => $this->newExaminationTypeId,
        ]);

        $this->rows[$detail->id] = [
            'template_id'       => '',
            'checked'           => false,
            'findings'          => '',
            'impression'        => '',
            'impression_source' => 'manual',
        ];

        $this->newExaminationTypeId = '';
        Flux::modal('add-row-modal')->close();
        $this->tindakan->refresh();
        Flux::toast(heading: 'Berhasil', text: 'Baris pemeriksaan ditambahkan.', variant: 'success');
    }

    public function confirmDeleteRow(int $id): void
    {
        $this->deletingRowId = $id;
        Flux::modal('delete-row-confirm')->show();
    }

    public function deleteRow(): void
    {
        $id = $this->deletingRowId;
        if ($id) {
            $detail = DetailTindakan::find($id);
            if ($detail) {
                // Hapus gambar terkait
                foreach ($detail->images as $img) {
                    Storage::disk('public')->delete($img->file_path);
                    $img->delete();
                }
                $detail->delete();
            }
            unset($this->rows[$id]);
            $this->tindakan->refresh();
            Flux::toast(heading: 'Berhasil', text: 'Baris pemeriksaan dihapus.', variant: 'success');
        }
        Flux::modal('delete-row-confirm')->close();
        $this->deletingRowId = null;
    }

    /**
     * Simpan data baris tabel (findings, impression, is_examined) TANPA validasi footer.
     * Dipanggil oleh tombol "Simpan Baris Data" di footer tabel pemeriksaan.
     */
    public function saveRowData(): void
    {
        try {
            foreach ($this->rows as $detailId => $row) {
                TindakanHasil::updateOrCreate(
                    ['detail_tindakan_id' => $detailId],
                    [
                        'findings'          => $row['findings'] ?? '',
                        'impression'        => $row['impression'] ?? '',
                        'impression_source' => $row['impression_source'] ?? 'manual',
                    ]
                );

                DetailTindakan::where('id', $detailId)->update([
                    'is_examined' => (bool) ($row['checked'] ?? false),
                ]);
            }

            Flux::toast(heading: 'Berhasil', text: 'Data baris pemeriksaan berhasil disimpan.', variant: 'success');
        } catch (\Throwable $e) {
            logger()->error('Gagal simpan baris pemeriksaan: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan: ' . $e->getMessage(), variant: 'danger');
        }
    }

    /**
     * Full-save: simpan dokter pembaca, operator, status, tanggal tahapan + semua baris.
     * Dipanggil oleh tombol "Simpan" di footer bawah form.
     */
    public function save(): void
    {
        $rules = [
            'dokterPembacaId'   => 'required|exists:users,id',
            'statusPemeriksaan' => 'required|in:' . implode(',', Tindakan::STATUSES),
        ];

        $validated = $this->validate($rules);

        try {
            foreach ($this->rows as $detailId => $row) {
                TindakanHasil::updateOrCreate(
                    ['detail_tindakan_id' => $detailId],
                    [
                        'findings'         => $row['findings'],
                        'impression'       => $row['impression'],
                        'impression_source'=> $row['impression_source'],
                    ]
                );
            }

            $this->tindakan->update([
            'dokter_pembaca_id' => $validated['dokterPembacaId'],
            'operator_1_id'     => $this->operator1Id ?: null,
            'operator_2_id'     => $this->operator2Id ?: null,
            'operator_3_id'     => $this->operator3Id ?: null,
            'operator_4_id'     => $this->operator4Id ?: null,
            'operator_5_id'     => $this->operator5Id ?: null,
            'status'            => $validated['statusPemeriksaan'],
            'tgl_datang'        => $this->tglDatang ?: null,
            'tgl_valid'         => $this->tglValid ?: null,
            'tgl_periksa'       => $this->tglPeriksa ?: null,
            'tgl_selesai'       => $this->tglSelesai ?: null,
            'tgl_serah'         => $this->tglSerah ?: null,
        ]);

        $this->tindakan->refresh();

        // Sinkronkan ulang state form supaya format tetap konsisten setelah refresh
        $this->tglDatang  = $this->tindakan->tgl_datang?->format('Y-m-d\TH:i') ?? '';
        $this->tglValid   = $this->tindakan->tgl_valid?->format('Y-m-d\TH:i') ?? '';
        $this->tglPeriksa = $this->tindakan->tgl_periksa?->format('Y-m-d\TH:i') ?? '';
        $this->tglSelesai = $this->tindakan->tgl_selesai?->format('Y-m-d\TH:i') ?? '';
        $this->tglSerah   = $this->tindakan->tgl_serah?->format('Y-m-d\TH:i') ?? '';

            Flux::toast(heading: 'Berhasil', text: 'Data pemeriksaan berhasil disimpan.', variant: 'success');
        } catch (\Throwable $e) {
            logger()->error('Gagal simpan pemeriksaan: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function with(): array
    {
        $existingTypeIds = $this->tindakan->detailTindakan->pluck('examination_type_id')->toArray();

        return [
            'detailTindakanList' => $this->tindakan->detailTindakan()->with(['examinationType', 'hasil', 'images'])->get(),
            'templateOptions'    => ReportMasterTemplate::where('is_active', true)->orderBy('template_name')->get(),
            'radiologistList'    => User::where('role', 'radiologist')->orderBy('name')->get(),
            'operatorList'       => User::orderBy('name')->get(),
            'cpptList'           => Cppt::where('patient_id', $this->tindakan->patient_id)->latest('tanggal_jam')->get(),
            'totalHarga'         => $this->tindakan->detailTindakan()->sum('price'),
            'examinationTypeList'=> ExaminationType::orderBy('name')->whereNotIn('id', $existingTypeIds)->get(),
            'viewingImages'      => $this->viewingDetailId
                ? DetailTindakan::find($this->viewingDetailId)?->images ?? collect()
                : collect(),
            'statusLabels'       => [
                'order'   => 'Order',
                'datang'  => 'Datang',
                'valid'   => 'Valid',
                'proses'  => 'Proses',
                'selesai' => 'Selesai',
                'serah'   => 'Serah',
            ],
        ];
    }
} ?>

<div class="space-y-4">

    {{-- ===== HEADER INFO PASIEN ===== --}}
    <flux:card class="text-sm" style="border-left: 3px solid #408BFF;">
        @php
            $isMale = strtolower($tindakan->patient->gender ?? 'l') === 'l';
            $svgName = $isMale ? 'male.svg' : 'female.svg';
            $hasSvg = file_exists(public_path('images/' . $svgName));
        @endphp
        <div class="flex items-start gap-6">
            <div class="shrink-0 w-28 h-28 rounded-full overflow-hidden flex items-center justify-center border shadow-sm {{ $isMale ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/40 dark:border-blue-800' : 'bg-rose-50 border-rose-200 dark:bg-rose-900/40 dark:border-rose-800' }}">
                @if($hasSvg)
                    <img src="{{ asset('images/' . $svgName) }}" alt="{{ $isMale ? 'Laki-laki' : 'Perempuan' }}" class="w-full h-full object-cover p-1.5 self-center" />
                @else
                    <div class="flex flex-col items-center justify-center gap-1 text-3xl font-bold select-none {{ $isMale ? 'text-blue-700 dark:text-blue-300' : 'text-rose-700 dark:text-rose-300' }}">
                        <span>{{ strtoupper(substr($tindakan->patient->name, 0, 1)) }}</span>
                        <span class="text-[10px] font-normal uppercase tracking-wider px-1.5 py-0.5 rounded-full {{ $isMale ? 'bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-rose-200 text-rose-800 dark:bg-rose-800 dark:text-rose-200' }}">
                            {{ $isMale ? 'L' : 'P' }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <flux:heading size="lg" class="mb-4">
                    Pelayanan - Radiologi | Pemeriksaan: {{ $tindakan->patient->name }}
                </flux:heading>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-2 md:divide-x md:divide-zinc-200 dark:md:divide-zinc-700">
                    <table class="text-sm border-separate border-spacing-y-1 w-full self-start">
                        <tbody>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">No. Reg</td><td>: {{ $tindakan->registrasi->no_registrasi ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">No. Order</td><td>: {{ $tindakan->no_order ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">No. RM</td><td>: {{ $tindakan->patient->mrn }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">NIK</td><td>: {{ $tindakan->patient->nik ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">Alamat</td><td>: {{ $tindakan->patient->address ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">Tgl. Lahir</td><td>: {{ $tindakan->patient->date_of_birth?->format('d-m-Y') }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-32">Umur / JK</td><td>: {{ $tindakan->patient->date_of_birth?->age }} Th / {{ $tindakan->patient->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
                        </tbody>
                    </table>

                    <table class="text-sm border-separate border-spacing-y-1 w-full self-start md:pl-8">
                        <tbody>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Lokasi Asal</td><td>: {{ $tindakan->pelayanan?->masterPoli?->name ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Alergi</td><td>: {{ $tindakan->registrasi->alergi ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Penyakit Khusus</td><td>: {{ $tindakan->registrasi->penyakit_khusus ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Tgl. Order</td><td>: {{ $tindakan->created_at->format('d-m-Y H:i') }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Dokter Pengirim</td><td>: {{ $tindakan->referring_doctor_name ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Dokter PJ</td><td>: {{ $tindakan->dokterPembaca?->name ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Tipe Order</td><td>: {{ strtoupper($tindakan->tipe_order) }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-36">Ket. Klinis</td><td>: {{ $tindakan->clinical_notes ?? '-' }}</td></tr>
                        </tbody>
                    </table>

                    <table class="text-sm border-separate border-spacing-y-1 w-full self-start md:pl-8">
                        <tbody>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-28">Jenis Pasien</td><td>: {{ $tindakan->registrasi->jenis_pasien ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-28">Kelas</td><td>: {{ $tindakan->registrasi->kelas_layanan ?? '-' }}</td></tr>
                            <tr><td class="font-semibold pr-3 align-top whitespace-nowrap w-28">Asal Pasien</td><td>: {{ $tindakan->registrasi->asal_pasien ?? '-' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- ===== TABS ===== --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-1">
            @foreach(['pemeriksaan' => ['icon' => 'document-text', 'label' => 'Pemeriksaan'], 'tarif' => ['icon' => 'banknotes', 'label' => 'Tarif Tindakan'], 'cppt' => ['icon' => 'clipboard-document-list', 'label' => 'CPPT']] as $tab => $cfg)
                <button
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-2.5 inline-flex items-center gap-2 border-b-2 font-medium text-sm transition-colors outline-none
                        {{ $activeTab === $tab
                            ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                            : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
                >
                    <flux:icon :icon="$cfg['icon']" class="size-4" />
                    {{ $cfg['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ===== TAB: PEMERIKSAAN ===== --}}
    @if ($activeTab === 'pemeriksaan')
    <div class="space-y-0"
         x-data="{ activeReviewId: null }"
         x-on:open-review-modal.window="activeReviewId = $event.detail.detailId; $dispatch('modal-show', { name: 'review-modal' })"
    >

        {{-- Tabel Pemeriksaan --}}
        <flux:card class="mt-4 p-4 overflow-hidden">
            <flux:table container:class="max-h-[500px] overflow-x-auto">
                <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                    <flux:table.column align="center" class="w-16">#</flux:table.column>
                    <flux:table.column align="center" class="w-16">Check</flux:table.column>
                    <flux:table.column class="w-48 min-w-[180px]">Tindakan</flux:table.column>
                    <flux:table.column align="center" class="w-16">QTY</flux:table.column>
                    <flux:table.column class="w-48 min-w-[160px]">Template</flux:table.column>
                    <flux:table.column class="w-[420px] min-w-[350px]">Deskripsi</flux:table.column>
                    <flux:table.column class="w-[420px] min-w-[350px]">Kesan</flux:table.column>
                    <flux:table.column align="center" class="w-36">Gambar</flux:table.column>
                    <flux:table.column align="center" class="w-32">Expertise</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($detailTindakanList as $detail)
                    <flux:table.row class="align-top" wire:key="row-{{ $detail->id }}">
                        {{-- Hapus --}}
                        <flux:table.cell align="center" class="align-middle">
                            <div class="flex items-center justify-center h-full min-h-[140px]">
                                <flux:button wire:click="confirmDeleteRow({{ $detail->id }})" variant="ghost" size="sm" class="text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">
                                    <x-heroicon-o-trash class="size-4" />
                                </flux:button>
                            </div>
                        </flux:table.cell>

                        {{-- Checkbox: menandai baris sudah diperiksa --}}
                        <flux:table.cell align="center" class="align-middle">
                            <div class="flex items-center justify-center h-full min-h-[140px]">
                                <input
                                    type="checkbox"
                                    wire:model="rows.{{ $detail->id }}.checked"
                                    class="rounded border-zinc-300 dark:border-zinc-700 text-blue-600 focus:ring-blue-500 cursor-pointer size-4"
                                />
                            </div>
                        </flux:table.cell>

                        {{-- Nama Tindakan --}}
                        <flux:table.cell class="align-middle">
                            <div class="flex items-center h-full min-h-[140px]">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $detail->examinationType->name }}</span>
                            </div>
                        </flux:table.cell>

                        {{-- QTY --}}
                        <flux:table.cell align="center" class="align-middle text-zinc-600 dark:text-zinc-400 font-medium">
                            <div class="flex items-center justify-center h-full min-h-[140px]">
                                1
                            </div>
                        </flux:table.cell>

                        {{-- Template --}}
                        <flux:table.cell class="align-middle">
                            <div class="p-1 min-h-[140px] flex items-center">
                                <select
                                    wire:model="rows.{{ $detail->id }}.template_id"
                                    wire:change="applyTemplate({{ $detail->id }})"
                                    class="w-full text-xs border border-zinc-300 dark:border-zinc-700 rounded-lg px-2.5 py-2 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:ring-1 focus:ring-blue-500 shadow-sm"
                                >
                                    <option value="">- Pilih -</option>
                                    @foreach($templateOptions as $tpl)
                                        <option value="{{ $tpl->id }}">{{ $tpl->template_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </flux:table.cell>

                        {{-- Deskripsi / Findings --}}
                        <flux:table.cell>
                            <div class="py-2">
                                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden focus-within:ring-1 focus-within:ring-blue-500 bg-white dark:bg-zinc-800 shadow-sm">
                                    <textarea
                                        wire:model="rows.{{ $detail->id }}.findings"
                                        rows="5"
                                        class="w-full text-xs p-2.5 bg-transparent text-zinc-700 dark:text-zinc-300 focus:outline-none resize-y border-none block"
                                        placeholder="Tuliskan findings di sini..."
                                    ></textarea>
                                    <div class="flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/50 px-2 py-1.5 min-h-[44px]">
                                        <flux:button
                                            wire:click="generateImpression({{ $detail->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="generateImpression({{ $detail->id }})"
                                            size="xs"
                                            color="purple"
                                            variant="subtle"
                                            icon="sparkles"
                                        >
                                            <span wire:loading.remove wire:target="generateImpression({{ $detail->id }})">Generate Impression</span>
                                            <span wire:loading wire:target="generateImpression({{ $detail->id }})">Generating...</span>
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Kesan / Impression --}}
                        <flux:table.cell>
                            <div class="py-2">
                                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden focus-within:ring-1 focus-within:ring-blue-500 bg-white dark:bg-zinc-800 shadow-sm">
                                    <textarea
                                        wire:model="rows.{{ $detail->id }}.impression"
                                        rows="5"
                                        class="w-full text-xs p-2.5 bg-transparent text-zinc-700 dark:text-zinc-300 focus:outline-none resize-y border-none block"
                                        placeholder="Kesan / impression..."
                                    ></textarea>
                                    <div class="flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/50 px-2 py-1.5 min-h-[44px]">
                                        <flux:button
                                            wire:click="reviewReport({{ $detail->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="reviewReport({{ $detail->id }})"
                                            size="xs"
                                            variant="subtle"
                                            icon="magnifying-glass"
                                        >
                                            <span wire:loading.remove wire:target="reviewReport({{ $detail->id }})">Review Report</span>
                                            <span wire:loading wire:target="reviewReport({{ $detail->id }})">Reviewing...</span>
                                        </flux:button>

                                        @php
                                            $source = $rows[$detail->id]['impression_source'] ?? 'manual';
                                        @endphp
                                        <flux:badge
                                            variant="pill"
                                            :color="match($source) {
                                                'ai_generated' => 'purple',
                                                'ai_edited'    => 'indigo',
                                                default        => 'zinc',
                                            }"
                                            size="sm"
                                        >
                                            {{ match($source) {
                                                'ai_generated' => 'AI Generated',
                                                'ai_edited'    => 'AI Edited',
                                                default        => 'Manual',
                                            } }}
                                        </flux:badge>
                                    </div>
                                </div>
                            </div>
                        </flux:table.cell>

                        {{-- Gambar --}}
                        <flux:table.cell align="center" class="align-middle">
                            <div class="flex flex-col items-center justify-center gap-2 h-full min-h-[140px] p-1">
                                {{-- Upload image --}}
                                <label 
                                    for="file-upload-{{ $detail->id }}"
                                    x-on:click="$wire.uploadingDetailId = {{ $detail->id }}"
                                    class="cursor-pointer inline-flex items-center justify-center gap-1 text-xs px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition-colors w-full shadow-sm"
                                >
                                    <flux:icon.plus class="size-3.5" />
                                    Tambah File
                                </label>
                                <input
                                    type="file"
                                    id="file-upload-{{ $detail->id }}"
                                    name="file-upload-{{ $detail->id }}"
                                    accept="image/*"
                                    class="hidden"
                                    wire:model="singleImageUpload"
                                />

                                {{-- Loading indicator --}}
                                <div x-show="$wire.uploadingDetailId === {{ $detail->id }}" class="text-xs text-zinc-400">
                                    <span wire:loading wire:target="singleImageUpload">
                                        Mengupload...
                                    </span>
                                </div>

                                {{-- Tombol Lihat jika ada gambar --}}
                                @if($detail->images->isNotEmpty())
                                    <flux:button
                                        wire:click="openImageModal({{ $detail->id }})"
                                        size="sm"
                                        variant="subtle"
                                        color="emerald"
                                        icon="photo"
                                        class="w-full"
                                    >
                                        Lihat ({{ $detail->images->count() }})
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>

                        {{-- Expertise --}}
                        <flux:table.cell align="center" class="align-middle">
                            <div class="flex items-center justify-center h-full min-h-[140px] p-1">
                                <flux:button
                                    href="{{ route('radiology.expertise.pdf', ['tindakan' => $tindakan->id, 'detail' => $detail->id]) }}"
                                    target="_blank"
                                    size="sm"
                                    variant="subtle"
                                    icon="printer"
                                >
                                    Cetak
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9" class="px-4 py-8 text-center text-zinc-400 text-sm">
                            Belum ada baris pemeriksaan. Klik <strong>+ Tambah Baris Baru</strong> untuk memulai.
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Footer Tabel: Tambah Baris + Aksi --}}
        <div class="flex items-center justify-between py-3 px-1">
            <flux:button variant="ghost" wire:click="openAddRowModal" icon="plus">Tambah Baris Baru</flux:button>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('radiology.expertise.all.pdf', $tindakan) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 text-sm font-medium transition-colors"
                >
                    <flux:icon.printer class="size-4" />
                    Cetak Semua
                </a>
                <button
                    wire:click="saveRowData"
                    wire:loading.attr="disabled"
                    wire:target="saveRowData"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors disabled:opacity-60"
                >
                    <flux:icon.check class="size-4" wire:loading.remove wire:target="saveRowData" />
                    <flux:icon.arrow-path class="size-4 animate-spin" wire:loading wire:target="saveRowData" />
                    Simpan Baris Data
                </button>
            </div>
        </div>

        {{-- ===== SECTION FOOTER FORM ===== --}}
        <flux:card class="mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Kiri: Tanggal Tahapan --}}
                <div class="space-y-3">
                    <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide text-xs mb-3">Tanggal Tahapan</flux:heading>

                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400 w-28 shrink-0">Tgl. Order</span>
                        <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ $tindakan->created_at->format('d-m-Y H:i') }}</span>
                    </div>

                    @foreach([
                        'tglDatang'  => 'Tgl. Datang',
                        'tglValid'   => 'Tgl. Valid',
                        'tglPeriksa' => 'Tgl. Periksa',
                        'tglSelesai' => 'Tgl. Selesai',
                        'tglSerah'   => 'Tgl. Serah',
                    ] as $prop => $label)
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400 w-28 shrink-0">{{ $label }}</span>
                            <input
                                type="datetime-local"
                                max="{{ date('Y-m-d\TH:i') }}"
                                wire:model="{{ $prop }}"
                                class="flex-1 max-w-[220px] text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg px-2.5 py-1.5 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>
                    @endforeach
                </div>

                {{-- Kanan: Dokter & Operator --}}
                <div class="space-y-3">
                    <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide text-xs mb-3">Petugas</flux:heading>
                    <flux:field>
                        <flux:label badge="Requiered">Dokter Pembaca</flux:label>
                        <flux:select wire:model="dokterPembacaId">
                            <flux:select.option value="">- Pilih -</flux:select.option>
                            @foreach($radiologistList as $r)
                                <flux:select.option value="{{ $r->id }}">{{ $r->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="dokterPembacaId" />
                    </flux:field>

                    @foreach([1,2,3,4,5] as $n)
                        <flux:field>
                            <flux:label>Operator {{ $n }}{{ $n === 1 ? ' *' : '' }}</flux:label>
                            <flux:select wire:model="operator{{ $n }}Id">
                                <flux:select.option value="">- Pilih -</flux:select.option>
                                @foreach($operatorList as $op)
                                    <flux:select.option value="{{ $op->id }}">{{ $op->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endforeach
                </div>
            </div>

            {{-- Status Pemeriksaan - Radio Button Horizontal --}}
            <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                <div class="flex items-center gap-4 flex-wrap">
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 shrink-0">Status Pemeriksaan <span class="text-red-500">*</span></span>
                    @foreach($statusLabels as $value => $label)
                        <label class="inline-flex items-center gap-2 cursor-pointer group">
                            <input
                                type="radio"
                                wire:model="statusPemeriksaan"
                                value="{{ $value }}"
                                class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                            />
                            <span class="text-sm {{ $statusPemeriksaan === $value ? 'text-blue-600 font-semibold dark:text-blue-400' : 'text-zinc-600 dark:text-zinc-400' }} group-hover:text-zinc-800 dark:group-hover:text-zinc-200 transition-colors">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="statusPemeriksaan" />
            </div>

            <div class="flex justify-end mt-5">
                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-colors disabled:opacity-60"
                >
                    <flux:icon.check class="size-4" wire:loading.remove wire:target="save" />
                    <flux:icon.arrow-path class="size-4 animate-spin" wire:loading wire:target="save" />
                    Simpan
                </button>
            </div>
        </flux:card>

        {{-- ===== MODAL: REVIEW LELAP AI ===== --}}
        <flux:modal name="review-modal" class="w-full max-w-lg">
            <div x-data="{ get result() { return activeReviewId ? ($wire.reviewResults[activeReviewId] || null) : null } }">
                <div class="space-y-4">
                    <div>
                        <flux:heading size="lg">Review Report</flux:heading>
                    </div>

                    <flux:separator />

                    <template x-if="result">
                        <div class="space-y-4">
                            <!-- Completeness -->
                            <div class="space-y-2">
                                <flux:heading size="sm" class="font-semibold text-zinc-800 dark:text-zinc-200">Completeness</flux:heading>
                                <div>
                                    <template x-if="result.completeness.passed">
                                        <div class="flex items-center gap-2">
                                            <flux:badge color="green" size="sm">✓ Success</flux:badge>
                                            <flux:text size="sm">Semua bagian deskripsi lengkap.</flux:text>
                                        </div>
                                    </template>
                                    <template x-if="!result.completeness.passed">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <flux:badge color="red" size="sm">⚠ Missing Items</flux:badge>
                                                <flux:text size="sm" class="text-red-500 font-medium">Belum lengkap. Bagian berikut tidak ditemukan:</flux:text>
                                            </div>
                                            <ul class="list-disc pl-5 text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                                                <template x-for="item in result.completeness.missing">
                                                    <li x-text="item"></li>
                                                </template>
                                            </ul>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <flux:separator />

                            <!-- Consistency -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="sm" class="font-semibold text-zinc-800 dark:text-zinc-200">Consistency</flux:heading>
                                    <template x-if="result.ai_review">
                                        <flux:badge size="sm" :color="null" x-bind:color="result.ai_review.consistency.status === 'ok' ? 'green' : 'red'"
                                            x-text="result.ai_review.consistency.status === 'ok' ? '✓ OK' : '⚠ Warning'"></flux:badge>
                                    </template>
                                </div>
                                <flux:text size="sm" x-text="result.ai_review ? result.ai_review.consistency.message : '-'"></flux:text>
                            </div>

                            <flux:separator />

                            <!-- Terminology -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="sm" class="font-semibold text-zinc-800 dark:text-zinc-200">Terminology</flux:heading>
                                    <template x-if="result.ai_review">
                                        <flux:badge size="sm" x-bind:color="result.ai_review.terminology.status === 'ok' ? 'green' : 'red'"
                                            x-text="result.ai_review.terminology.status === 'ok' ? '✓ OK' : '⚠ Warning'"></flux:badge>
                                    </template>
                                </div>
                                <flux:text size="sm" x-text="result.ai_review ? result.ai_review.terminology.message : '-'"></flux:text>
                            </div>

                            <flux:separator />

                            <!-- Typo -->
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="sm" class="font-semibold text-zinc-800 dark:text-zinc-200">Typo</flux:heading>
                                    <template x-if="result.ai_review">
                                        <flux:badge size="sm" x-bind:color="result.ai_review.typo.status === 'ok' ? 'green' : 'red'"
                                            x-text="result.ai_review.typo.status === 'ok' ? '✓ OK' : '⚠ Warning'"></flux:badge>
                                    </template>
                                </div>
                                <flux:text size="sm" x-text="result.ai_review ? result.ai_review.typo.message : '-'"></flux:text>
                            </div>

                            <flux:separator />

                            <!-- Recommendation -->
                            <div class="space-y-1">
                                <flux:heading size="sm" class="font-semibold text-zinc-800 dark:text-zinc-200">Recommendation</flux:heading>
                                <flux:text size="sm" class="italic" x-text="result.ai_review ? result.ai_review.recommendation : '-'"></flux:text>
                            </div>
                        </div>
                    </template>

                    <template x-if="!result">
                        <div class="py-8 text-center text-zinc-400">
                            <flux:text>Loading review data...</flux:text>
                        </div>
                    </template>

                    <div class="flex justify-end gap-2 pt-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </div>
        </flux:modal>

    </div>
    @endif

    {{-- ===== TAB: TARIF TINDAKAN ===== --}}
    @if ($activeTab === 'tarif')
    <flux:card class="mt-4">
        <flux:heading size="lg" class="mb-4">Tarif Tindakan</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-8">No</flux:table.column>
                <flux:table.column>Jenis Pemeriksaan</flux:table.column>
                <flux:table.column class="text-right">Harga</flux:table.column>
                <flux:table.column class="w-12 text-center">QTY</flux:table.column>
                <flux:table.column class="text-right">Total Biaya</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($detailTindakanList as $i => $detail)
                <flux:table.row wire:key="tarif-row-{{ $detail->id }}">
                    <flux:table.cell class="text-zinc-500">{{ $i + 1 }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-800 dark:text-zinc-200">{{ $detail->examinationType->name }}</flux:table.cell>
                    <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-300">Rp {{ number_format($detail->price, 0, ',', '.') }}</flux:table.cell>
                    <flux:table.cell class="text-right text-zinc-600">1</flux:table.cell>
                    <flux:table.cell class="text-right font-medium text-zinc-800 dark:text-zinc-200">Rp {{ number_format($detail->price, 0, ',', '.') }}</flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-400">Belum ada item tindakan.</flux:table.cell>
                </flux:table.row>
                @endforelse
                <flux:table.row class="bg-zinc-50 dark:bg-zinc-800 font-bold">
                    <flux:table.cell colspan="4" class="text-right text-zinc-700 dark:text-zinc-200">Total</flux:table.cell>
                    <flux:table.cell class="text-right text-zinc-800 dark:text-zinc-100">Rp {{ number_format($totalHarga, 0, ',', '.') }}</flux:table.cell>
                </flux:table.row>
            </flux:table.rows>
        </flux:table>
    </flux:card>
    @endif

    {{-- ===== TAB: CPPT ===== --}}
    @if ($activeTab === 'cppt')
    <div class="space-y-3 mt-4">
        @forelse($cpptList as $cppt)
            <flux:card>
                <div class="flex justify-between text-xs text-zinc-500 mb-2">
                    <span>{{ $cppt->tanggal_jam->format('d-m-Y H:i') }} — {{ $cppt->lokasi_layanan }}</span>
                    <span>{{ $cppt->profesi_pengisi }} ({{ $cppt->ppa }})</span>
                </div>
                <div class="space-y-1 text-sm">
                    <p><strong>S:</strong> {{ $cppt->subjective }}</p>
                    <p><strong>O:</strong> {{ $cppt->objective }}</p>
                    <p><strong>A:</strong> {{ $cppt->assessment }}</p>
                    <p><strong>P:</strong> {{ $cppt->plan }}</p>
                </div>
            </flux:card>
        @empty
            <div class="text-center py-10 text-zinc-400">
                <flux:icon.clipboard-document-list class="size-10 mx-auto mb-2 opacity-40" />
                <p>Belum ada riwayat CPPT untuk pasien ini.</p>
            </div>
        @endforelse
    </div>
    @endif

    <flux:modal name="delete-row-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus Baris Pemeriksaan?</flux:heading>
            <flux:text>Tindakan ini tidak dapat dibatalkan. Baris pemeriksaan beserta semua gambar terkait akan dihapus secara permanen.</flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteRow">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="add-row-modal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Tambah Baris Pemeriksaan</flux:heading>
            </div>

            <flux:field>
                <flux:label>Jenis Pemeriksaan</flux:label>
                <flux:select wire:model.live="newExaminationTypeId">
                    <flux:select.option value="">- Pilih jenis pemeriksaan -</flux:select.option>
                    @foreach($examinationTypeList as $et)
                        <flux:select.option value="{{ $et->id }}">{{ $et->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if($examinationTypeList->isEmpty())
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">Semua jenis pemeriksaan sudah ditambahkan.</p>
                @endif
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="addRow" variant="primary" color="blue" x-bind:disabled="!$wire.newExaminationTypeId">
                    Tambahkan
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ===== MODAL: LIGHTBOX GAMBAR (Flux Modal) ===== --}}
    <flux:modal name="image-lightbox" class="w-full max-w-3xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Gambar Radiologi</flux:heading>
            </div>

            <flux:separator />

            @if($viewingImages->isEmpty())
                <p class="text-center text-zinc-400 py-8">Belum ada gambar yang diupload.</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($viewingImages as $img)
                        <div class="relative group rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800" wire:key="view-image-{{ $img->id }}">
                            {{-- Clickable image to zoom --}}
                            <img
                                src="{{ Storage::url($img->file_path) }}"
                                alt="Gambar Radiologi"
                                class="w-full h-40 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                                x-on:click="$wire.zoomedImageUrl = '{{ Storage::url($img->file_path) }}'; $dispatch('modal-show', { name: 'zoom-image-modal' })"
                            />
                            
                            {{-- Red "x" close/delete button in top-right corner --}}
                            <button
                                x-on:click="$wire.deletingImageId = {{ $img->id }}; $dispatch('modal-show', { name: 'delete-image-confirm' })"
                                class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded-full transition-colors shadow-md z-10"
                                title="Hapus gambar"
                            >
                                <flux:icon.x-mark class="size-3.5" />
                            </button>
                            
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 px-2 py-1 truncate bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">{{ basename($img->file_path) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- ===== MODAL: CONFIRM DELETE IMAGE ===== --}}
    <flux:modal name="delete-image-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus Gambar Radiologi?</flux:heading>
            <flux:text>Apakah Anda yakin ingin menghapus gambar ini secara permanen?</flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="confirmDeleteImage">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- ===== MODAL: ZOOM IMAGE PREVIEW ===== --}}
    <flux:modal name="zoom-image-modal" class="w-full max-w-4xl">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Detail Gambar Radiologi</flux:heading>
            </div>

            <flux:separator />

            <div class="flex items-center justify-center bg-zinc-950 rounded-lg p-2 overflow-hidden border border-zinc-200 dark:border-zinc-800">
                <img :src="$wire.zoomedImageUrl" class="max-w-full max-h-[70vh] object-contain rounded" />
            </div>
        </div>
    </flux:modal>

</div>