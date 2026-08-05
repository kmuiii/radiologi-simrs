<?php

use App\Models\Cppt;
use App\Models\DetailTindakan;
use App\Models\ReportMasterTemplate;
use App\Models\Tindakan;
use App\Models\TindakanHasil;
use App\Models\User;
use App\Services\RadiologyAIService;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public Tindakan $tindakan;

    public string $activeTab = 'pemeriksaan';

    // State per baris: [detail_tindakan_id => ['template_id'=>, 'findings'=>, 'impression'=>, 'impression_source'=>]]
    public array $rows = [];
    public array $reviewResults = [];

    // Footer
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

        $this->tindakan = $tindakan;
        $this->dokterPembacaId = (string) $tindakan->dokter_pembaca_id;
        $this->operator1Id = (string) $tindakan->operator_1_id;
        $this->operator2Id = (string) $tindakan->operator_2_id;
        $this->operator3Id = (string) $tindakan->operator_3_id;
        $this->operator4Id = (string) $tindakan->operator_4_id;
        $this->operator5Id = (string) $tindakan->operator_5_id;
        $this->statusPemeriksaan = $tindakan->status;

        foreach ($tindakan->detailTindakan as $detail) {
            $this->rows[$detail->id] = [
                'template_id' => '',
                'findings' => $detail->hasil->findings ?? '',
                'impression' => $detail->hasil->impression ?? '',
                'impression_source' => $detail->hasil->impression_source ?? 'manual',
            ];
        }
    }

    // terapkan template ke baris tertentu, jika ada template_id yang dipilih
    public function applyTemplate(int $detailId): void
    {
        $templateId = $this->rows[$detailId]['template_id'] ?? null;

        if (! $templateId) {
            return;
        }

        $template = ReportMasterTemplate::find($templateId);

        if ($template) {
            $this->rows[$detailId]['findings'] = $template->uraian;
        }
    }

    // gunakan AI untuk menghasilkan kesan (impression) berdasarkan findings dan clinical notes
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

    // gunakan AI untuk meninjau laporan (findings + impression) dan cek kelengkapan serta konsistensi
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
            'ai_review' => $aiReview,
        ];
    }

    public function addRow(int $examinationTypeId): void
    {
        $detail = DetailTindakan::create([
            'tindakan_id' => $this->tindakan->id,
            'examination_type_id' => $examinationTypeId,
        ]);

        $this->rows[$detail->id] = ['template_id' => '', 'findings' => '', 'impression' => '', 'impression_source' => 'manual'];
        $this->tindakan->refresh();
    }

    public function deleteRow(int $detailId): void
    {
        DetailTindakan::find($detailId)?->delete();
        unset($this->rows[$detailId]);
        $this->tindakan->refresh();
    }

    public function save(): void
    {
        $rules = [
            'dokterPembacaId' => 'required|exists:users,id',
            'statusPemeriksaan' => 'required|in:' . implode(',', Tindakan::STATUSES),
        ];

        $validated = $this->validate($rules);

        try {
            foreach ($this->rows as $detailId => $row) {
                TindakanHasil::updateOrCreate(
                    ['detail_tindakan_id' => $detailId],
                    [
                        'findings' => $row['findings'],
                        'impression' => $row['impression'],
                        'impression_source' => $row['impression_source'],
                    ]
                );
            }

            $stageOrder = Tindakan::STATUSES;
            $currentIndex = array_search($this->statusPemeriksaan, $stageOrder);
            $tglFields = ['tgl_datang', 'tgl_valid', 'tgl_periksa', 'tgl_selesai', 'tgl_serah'];

            $update = [
                'dokter_pembaca_id' => $validated['dokterPembacaId'],
                'operator_1_id' => $this->operator1Id ?: null,
                'operator_2_id' => $this->operator2Id ?: null,
                'operator_3_id' => $this->operator3Id ?: null,
                'operator_4_id' => $this->operator4Id ?: null,
                'operator_5_id' => $this->operator5Id ?: null,
                'status' => $validated['statusPemeriksaan'],
            ];

            // Auto-timestamp tahap yang baru dicapai, tanpa menimpa yang sudah ada
            for ($i = 1; $i <= $currentIndex; $i++) {
                $field = $tglFields[$i - 1];
                if (empty($this->tindakan->$field)) {
                    $update[$field] = now();
                }
            }

            $this->tindakan->update($update);

            Flux::toast(heading: 'Berhasil', text: 'Data pemeriksaan berhasil disimpan.', variant: 'success');
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data.', variant: 'danger');
        }
    }

    public function with(): array
    {
        return [
            'detailTindakanList' => $this->tindakan->detailTindakan()->with(['examinationType', 'hasil'])->get(),
            'templateOptions' => ReportMasterTemplate::where('is_active', true)->orderBy('template_name')->get(),
            'radiologistList' => User::where('role', 'radiologist')->orderBy('name')->get(),
            'operatorList' => User::orderBy('name')->get(),
            'cpptList' => Cppt::where('patient_id', $this->tindakan->patient_id)->latest('tanggal_jam')->get(),
            'totalHarga' => $this->tindakan->detailTindakan()->sum('price'),
        ];
    }
} ?>

<div class="space-y-6">

    {{-- Header Info Pasien --}}
    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">
                Pelayanan - Radiologi | Pemeriksaan: {{ $tindakan->patient->name }}
            </flux:heading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p><strong>No. RM:</strong> {{ $tindakan->patient->mrn }}</p>
                <p><strong>NIK:</strong> {{ $tindakan->patient->nik }}</p>
                <p><strong>Alamat:</strong> {{ $tindakan->patient->address }}</p>
            </div>
            <div>
                <p><strong>Tgl. Lahir:</strong> {{ $tindakan->patient->date_of_birth->format('d-m-Y') }}</p>
                <p><strong>Dokter Pengirim:</strong> {{ $tindakan->referring_doctor_name ?? '-' }}</p>
                <p><strong>Ket. Klinis:</strong> {{ $tindakan->clinical_notes ?? '-' }}</p>
            </div>
            <div>
                <p><strong>Poli Asal:</strong> {{ $tindakan->pelayanan->masterPoli->name ?? '-' }}</p>
                <p><strong>Tgl. Order:</strong> {{ $tindakan->created_at->format('d-m-Y H:i') }}</p>
            </div>
        </div>
    </flux:card>

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-800">
        <nav class="flex space-x-6" aria-label="Tabs">
            <button
                wire:click="$set('activeTab', 'pemeriksaan')"
                class="pb-4 px-1 inline-flex items-center gap-2 border-b-2 font-medium text-sm transition-all duration-200 outline-none {{ $activeTab === 'pemeriksaan' ? 'border-zinc-800 text-zinc-800 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.document-text class="size-5" />
                <span>Pemeriksaan</span>
            </button>
            <button
                wire:click="$set('activeTab', 'tarif')"
                class="pb-4 px-1 inline-flex items-center gap-2 border-b-2 font-medium text-sm transition-all duration-200 outline-none {{ $activeTab === 'tarif' ? 'border-zinc-800 text-zinc-800 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.banknotes class="size-5" />
                <span>Tarif Tindakan</span>
            </button>
            <button
                wire:click="$set('activeTab', 'cppt')"
                class="pb-4 px-1 inline-flex items-center gap-2 border-b-2 font-medium text-sm transition-all duration-200 outline-none {{ $activeTab === 'cppt' ? 'border-zinc-800 text-zinc-800 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <flux:icon.clipboard-document-list class="size-5" />
                <span>CPPT (Riwayat)</span>
            </button>
        </nav>
    </div>

    {{-- TAB: PEMERIKSAAN --}}
    @if ($activeTab === 'pemeriksaan')
            <div class="space-y-6 mt-4">
                @foreach($detailTindakanList as $detail)
                    <flux:card class="space-y-3">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ $detail->examinationType->name }}</flux:heading>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="deleteRow({{ $detail->id }})" />
                        </div>

                        <flux:field>
                            <flux:label>Template</flux:label>
                            <flux:select wire:model="rows.{{ $detail->id }}.template_id" wire:change="applyTemplate({{ $detail->id }})">
                                <flux:select.option value="">- Pilih Template (opsional) -</flux:select.option>
                                @foreach($templateOptions as $tpl)
                                    <flux:select.option value="{{ $tpl->id }}">{{ $tpl->template_name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Deskripsi (Findings)</flux:label>
                                <flux:textarea wire:model="rows.{{ $detail->id }}.findings" rows="8" />
                                <flux:button size="sm" class="mt-2" icon="sparkles" wire:click="generateImpression({{ $detail->id }})">
                                    Generate Kesan (AI)
                                </flux:button>
                            </flux:field>

                            <flux:field>
                                <flux:label>Kesan (Impression)</flux:label>
                                <flux:textarea wire:model="rows.{{ $detail->id }}.impression" rows="8" />
                                <flux:badge size="sm" class="mt-2">
                                    Sumber: {{ $rows[$detail->id]['impression_source'] ?? 'manual' }}
                                </flux:badge>
                            </flux:field>
                        </div>

                        <flux:button size="sm" variant="primary" icon="magnifying-glass" wire:click="reviewReport({{ $detail->id }})">
                            Review Laporan (AI)
                        </flux:button>

                        @if(isset($reviewResults[$detail->id]))
                            @php($r = $reviewResults[$detail->id])
                            <div class="border-l-4 border-green-500 bg-zinc-50 dark:bg-zinc-800 p-3 space-y-1 text-sm">
                                <p>{{ $r['completeness']['passed'] ? '✓ Completeness: lengkap.' : '⦸ Belum lengkap: ' . implode(', ', $r['completeness']['missing']) }}</p>
                                @if($r['ai_review'])
                                    <p>⚠ Consistency: {{ $r['ai_review']['consistency'] }}</p>
                                    <p>⚠ Terminology: {{ $r['ai_review']['terminology'] }}</p>
                                    <p>Typo: {{ $r['ai_review']['typo'] }}</p>
                                    <p class="italic">Rekomendasi: {{ $r['ai_review']['recommendation'] }}</p>
                                @endif
                            </div>
                        @endif
                    </flux:card>
                @endforeach

                <flux:card>
                    <flux:heading size="lg" class="mb-4">Data Pemeriksaan</flux:heading>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <flux:field>
                            <flux:label>Dokter Pembaca</flux:label>
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
                                <flux:label>Operator {{ $n }}</flux:label>
                                <flux:select wire:model="operator{{ $n }}Id">
                                    <flux:select.option value="">- Pilih -</flux:select.option>
                                    @foreach($operatorList as $op)
                                        <flux:select.option value="{{ $op->id }}">{{ $op->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        @endforeach
                    </div>

                    <flux:field>
                        <flux:label>Status Pemeriksaan</flux:label>
                        <flux:radio.group wire:model="statusPemeriksaan" variant="segmented">
                            <flux:radio value="order" label="Order" />
                            <flux:radio value="datang" label="Datang" />
                            <flux:radio value="valid" label="Valid" />
                            <flux:radio value="proses" label="Proses" />
                            <flux:radio value="selesai" label="Selesai" />
                            <flux:radio value="serah" label="Serah" />
                        </flux:radio.group>
                        <flux:error name="statusPemeriksaan" />
                    </flux:field>

                    <div class="flex justify-end mt-4">
                        <flux:button variant="primary" icon="check" wire:click="save">Simpan</flux:button>
                    </div>
                </flux:card>
            </div>
        @endif

        {{-- TAB: TARIF TINDAKAN --}}
        @if ($activeTab === 'tarif')
            <flux:card class="mt-4">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Jenis Pemeriksaan</flux:table.column>
                        <flux:table.column>Harga</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($detailTindakanList as $detail)
                            <flux:table.row>
                                <flux:table.cell>{{ $detail->examinationType->name }}</flux:table.cell>
                                <flux:table.cell>Rp {{ number_format($detail->price, 0, ',', '.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                <div class="text-right font-semibold mt-4">
                    Total: Rp {{ number_format($totalHarga, 0, ',', '.') }}
                </div>
            </flux:card>
        @endif

        {{-- TAB: CPPT --}}
        @if ($activeTab === 'cppt')
            <div class="space-y-4 mt-4">
                @forelse($cpptList as $cppt)
                    <flux:card>
                        <div class="flex justify-between text-sm text-zinc-500 mb-2">
                            <span>{{ $cppt->tanggal_jam->format('d-m-Y H:i') }} — {{ $cppt->lokasi_layanan }}</span>
                            <span>{{ $cppt->profesi_pengisi }} ({{ $cppt->ppa }})</span>
                        </div>
                        <p><strong>S:</strong> {{ $cppt->subjective }}</p>
                        <p><strong>O:</strong> {{ $cppt->objective }}</p>
                        <p><strong>A:</strong> {{ $cppt->assessment }}</p>
                        <p><strong>P:</strong> {{ $cppt->plan }}</p>
                    </flux:card>
                @empty
                    <flux:text class="text-zinc-400">Belum ada riwayat CPPT untuk pasien ini.</flux:text>
                @endforelse
            </div>
        @endif

</div>