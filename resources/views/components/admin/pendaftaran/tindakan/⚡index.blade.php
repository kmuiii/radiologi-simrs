<?php

use App\Models\JenisTindakan;
use App\Models\MasterPoli;
use App\Models\Patient;
use App\Models\Pelayanan;
use App\Models\Registrasi;
use App\Models\Tindakan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Filters untuk tabel utama
    public string $tglAwal = '';
    public string $tglAkhir = '';
    public string $lokasiAsal = '';
    public string $statusPeriksa = '';
    public string $tipeOrder = '';
    public string $cariPasien = '';
    public int $perPage = 10;

    // Modal Diterima
    public ?int $diterimaTindakanId = null;
    public string $diterimaTanggalJam = '';
    public string $diterimaPenerima = '';
    public string $diterimaStatus = 'belum_diterima';

    // Modal Hapus
    public ?int $deletingTindakanId = null;

    // Modal Tambah Order
    public string $activeModalTab = 'kunjungan'; // 'kunjungan' | 'datang_langsung'

    // Tab 1: Kunjungan Filter & Pagination
    public string $searchKunjungan = '';
    public int $perPageKunjungan = 10;

    // Tab 2: Datang Langsung Form Fields
    public string $dlTglRegistrasi = '';
    public string $dlPenjamin = 'UMUM';
    public string $dlPunyaNoRm = 'tidak'; // 'ya' | 'tidak'
    public string $dlSearchMrn = '';
    public ?int $dlPatientId = null;
    public string $dlNik = '';
    public string $dlTitle = 'Tn'; // 'An', 'Sdr', 'Tn', 'Ny', 'Bpn'
    public string $dlName = '';
    public string $dlGender = 'L';
    public string $dlTempatLahir = '';
    public string $dlDateOfBirth = '';
    public string $dlPhone = '';
    public string $dlAsalPasien = 'Datang Sendiri';
    public string $dlDokterName = '';
    public string $dlMasterPoliId = '';

    // Alamat Sesuai Identitas
    public string $dlAddress = '';
    public string $dlRt = '';
    public string $dlRw = '';
    public string $dlKodePos = '';
    public string $dlNegara = 'INDONESIA';
    public string $dlProvinceCode = '';
    public string $dlCityCode = '';
    public string $dlDistrictCode = '';
    public string $dlVillageCode = '';

    public function mount(): void
    {
        $this->tglAwal = now()->startOfMonth()->format('Y-m-d');
        $this->tglAkhir = now()->format('Y-m-d');
        $this->dlTglRegistrasi = now()->format('d-m-Y H:i:s');
    }

    public function updated($property): void
    {
        if (in_array($property, ['tglAwal', 'tglAkhir', 'lokasiAsal', 'statusPeriksa', 'tipeOrder', 'cariPasien', 'perPage'])) {
            $this->resetPage();
        }
        if (in_array($property, ['searchKunjungan', 'perPageKunjungan'])) {
            $this->resetPage('kunjungan-page');
        }
    }

    public function updatedDlProvinceCode(): void
    {
        $this->reset(['dlCityCode', 'dlDistrictCode', 'dlVillageCode']);
    }

    public function updatedDlCityCode(): void
    {
        $this->reset(['dlDistrictCode', 'dlVillageCode']);
    }

    public function updatedDlDistrictCode(): void
    {
        $this->reset(['dlVillageCode']);
    }

    public function updatedDlPunyaNoRm(): void
    {
        if ($this->dlPunyaNoRm === 'tidak') {
            $this->reset(['dlPatientId', 'dlSearchMrn']);
        }
    }

    public function resetFilter(): void
    {
        $this->reset(['lokasiAsal', 'statusPeriksa', 'tipeOrder', 'cariPasien']);
        $this->tglAwal = now()->startOfMonth()->format('Y-m-d');
        $this->tglAkhir = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function openTambahOrderModal(): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        $this->activeModalTab = 'kunjungan';
        $this->dlTglRegistrasi = now()->format('d-m-Y H:i:s');
        Flux::modal('tambah-order-modal')->show();
    }

    public function searchPatientByMrn(): void
    {
        if (blank($this->dlSearchMrn)) {
            Flux::toast(heading: 'Peringatan', text: 'Masukkan No. Rekammedis terlebih dahulu.', variant: 'warning');
            return;
        }

        $patient = Patient::where('mrn', 'ilike', trim($this->dlSearchMrn))->first();

        if (! $patient) {
            Flux::toast(heading: 'Tidak Ditemukan', text: "Pasien dengan No. RM \"{$this->dlSearchMrn}\" tidak ditemukan.", variant: 'danger');
            $this->dlPatientId = null;
            return;
        }

        $this->dlPatientId = $patient->id;
        $this->dlNik = $patient->nik ?? '';
        $this->dlName = $patient->name;
        $this->dlGender = $patient->gender;
        $this->dlTempatLahir = $patient->tempat_lahir ?? '';
        $this->dlDateOfBirth = $patient->date_of_birth?->format('Y-m-d') ?? '';
        $this->dlPhone = $patient->phone ?? '';
        $this->dlAddress = $patient->address ?? '';
        $this->dlRt = $patient->rt ?? '';
        $this->dlRw = $patient->rw ?? '';
        $this->dlKodePos = $patient->kode_pos ?? '';
        $this->dlProvinceCode = $patient->province_code ?? '';
        $this->dlCityCode = $patient->city_code ?? '';
        $this->dlDistrictCode = $patient->district_code ?? '';
        $this->dlVillageCode = $patient->village_code ?? '';

        Flux::toast(heading: 'Ditemukan', text: "Data pasien \"{$patient->name}\" berhasil dimuat.", variant: 'success');
    }

    public function selectKunjungan(int $registrasiId)
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        try {
            $reg = Registrasi::findOrFail($registrasiId);
            
            Flux::modal('tambah-order-modal')->close();
            
            $targetRoute = auth()->user()->role === 'admin' ? 'admin.pendaftaran.tindakan.create' : 'radiology.tindakan.create';
            return redirect()->route($targetRoute, ['registrasi_id' => $reg->id]);
        } catch (\Throwable $e) {
            logger()->error('Gagal memilih kunjungan: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat memilih kunjungan.', variant: 'danger');
        }
    }

    public function saveDatangLangsung()
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        if ($this->dlPunyaNoRm === 'ya' && ! $this->dlPatientId) {
            Flux::toast(heading: 'Peringatan', text: 'Silakan cari No. Rekammedis terlebih dahulu.', variant: 'warning');
            return;
        }

        if (! $this->dlPatientId) {
            $this->validate([
                'dlName' => 'required|string|max:255',
                'dlGender' => 'required|in:L,P',
            ], [
                'dlName.required' => 'Nama Pasien wajib diisi.',
                'dlGender.required' => 'Jenis Kelamin wajib dipilih.',
            ]);
        }

        try {
            $createdRegistrasi = null;

            DB::transaction(function () use (&$createdRegistrasi) {
                if ($this->dlPunyaNoRm === 'ya' && $this->dlPatientId) {
                    $patient = Patient::findOrFail($this->dlPatientId);
                } else {
                    $fullName = trim($this->dlTitle . ' ' . $this->dlName);
                    $patient = Patient::create([
                        'name' => $fullName,
                        'nik' => $this->dlNik ?: null,
                        'gender' => $this->dlGender,
                        'tempat_lahir' => $this->dlTempatLahir ?: null,
                        'date_of_birth' => $this->dlDateOfBirth ?: now()->subYears(25)->format('Y-m-d'),
                        'phone' => $this->dlPhone ?: null,
                        'address' => $this->dlAddress ?: null,
                        'rt' => $this->dlRt ?: null,
                        'rw' => $this->dlRw ?: null,
                        'kode_pos' => $this->dlKodePos ?: null,
                        'negara' => $this->dlNegara ?: 'INDONESIA',
                        'province_code' => $this->dlProvinceCode ?: null,
                        'city_code' => $this->dlCityCode ?: null,
                        'district_code' => $this->dlDistrictCode ?: null,
                        'village_code' => $this->dlVillageCode ?: null,
                    ]);
                }

                $createdRegistrasi = Registrasi::create([
                    'patient_id' => $patient->id,
                    'tanggal_registrasi' => now(),
                    'jenis_pasien' => $this->dlPenjamin,
                    'asal_pasien' => $this->dlAsalPasien,
                ]);

                $masterPoliId = $this->dlMasterPoliId ?: MasterPoli::first()?->id;
                $pelayanan = Pelayanan::create([
                    'registrasi_id' => $createdRegistrasi->id,
                    'master_poli_id' => $masterPoliId,
                    'dpjp_id' => null, // Provide default or assign based on selected doctor if any
                ]);
            });

            Flux::modal('tambah-order-modal')->close();

            $targetRoute = auth()->user()->role === 'admin' ? 'admin.pendaftaran.tindakan.create' : 'radiology.tindakan.create';
            return redirect()->route($targetRoute, ['registrasi_id' => $createdRegistrasi->id]);
        } catch (\Throwable $e) {
            logger()->error('Gagal membuat order datang langsung: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function openDiterimaModal(int $id): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        $tindakan = Tindakan::findOrFail($id);

        $this->diterimaTindakanId = $tindakan->id;
        $this->diterimaTanggalJam = $tindakan->tanggal_jam_diterima?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->diterimaPenerima = $tindakan->penerima ?? '';
        $this->diterimaStatus = $tindakan->status_diterima ?? 'belum_diterima';

        Flux::modal('diterima-form')->show();
    }

    public function saveDiterima(): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        $validated = $this->validate([
            'diterimaTanggalJam' => 'required|date',
            'diterimaPenerima' => 'nullable|string|max:255',
            'diterimaStatus' => 'required|in:diterima,belum_diterima,konfirmasi',
        ]);

        try {
            $tindakan = Tindakan::findOrFail($this->diterimaTindakanId);
            $tindakan->update([
                'tanggal_jam_diterima' => $validated['diterimaTanggalJam'],
                'penerima' => $validated['diterimaPenerima'],
                'status_diterima' => $validated['diterimaStatus'],
            ]);

            Flux::toast(heading: 'Berhasil', text: 'Data penerimaan berhasil disimpan.', variant: 'success');
            Flux::modal('diterima-form')->close();
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data.', variant: 'danger');
        }
    }

    public function confirmDelete(int $id): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        $this->deletingTindakanId = $id;
        Flux::modal('delete-tindakan-confirm')->show();
    }

    public function deleteOrder(): void
    {
        if (! in_array(auth()->user()->role, ['admin', 'radiologist'])) {
            abort(403, 'Aksi ini hanya dapat dilakukan oleh Admin atau Dokter Radiologi.');
        }
        try {
            $tindakan = Tindakan::findOrFail($this->deletingTindakanId);
            
            // Hapus folder gambar fisik terkait tindakan ini dari storage
            Storage::disk('public')->deleteDirectory("radiology/{$tindakan->id}");
            
            $tindakan->delete();

            Flux::toast(heading: 'Berhasil', text: 'Order berhasil dihapus.', variant: 'success');
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menghapus order.', variant: 'danger');
        }

        Flux::modal('delete-tindakan-confirm')->close();
        $this->deletingTindakanId = null;
    }

    public function with(): array
    {
        $radiologiId = JenisTindakan::where('name', 'Radiologi')->value('id');

        $query = Tindakan::with(['patient', 'registrasi', 'pelayanan.masterPoli', 'detailTindakan.examinationType'])
            ->where('jenis_tindakan_id', $radiologiId);

        if ($this->tglAwal) {
            $query->whereDate('created_at', '>=', $this->tglAwal);
        }
        if ($this->tglAkhir) {
            $query->whereDate('created_at', '<=', $this->tglAkhir);
        }
        if ($this->lokasiAsal) {
            $query->whereHas('pelayanan', fn ($q) => $q->where('master_poli_id', $this->lokasiAsal));
        }
        if ($this->statusPeriksa) {
            $query->where('status', $this->statusPeriksa);
        }
        if ($this->tipeOrder) {
            $query->where('tipe_order', $this->tipeOrder);
        }
        if ($this->cariPasien) {
            $query->whereHas('patient', function ($q) {
                $q->where('name', 'ilike', "%{$this->cariPasien}%")
                    ->orWhere('mrn', 'ilike', "%{$this->cariPasien}%");
            });
        }

        // Query untuk Modal Tab Kunjungan
        $kunjunganQuery = Registrasi::with(['patient', 'pelayanan.masterPoli', 'pelayanan.dpjp']);

        if ($this->searchKunjungan) {
            $kunjunganQuery->where(function ($q) {
                $q->where('no_registrasi', 'ilike', "%{$this->searchKunjungan}%")
                  ->orWhereHas('patient', function ($pq) {
                      $pq->where('name', 'ilike', "%{$this->searchKunjungan}%")
                         ->orWhere('mrn', 'ilike', "%{$this->searchKunjungan}%");
                  });
            });
        }

        return [
            'tindakanList' => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($this->perPage),
            'kunjunganList' => $kunjunganQuery->latest()->paginate($this->perPageKunjungan, pageName: 'kunjungan-page'),
            'poliList' => MasterPoli::all(),
            'dokterList' => User::whereIn('role', ['dokter', 'radiologist'])->orderBy('name')->get(),
            'provinceList' => Province::orderBy('name')->get(),
            'cityList' => $this->dlProvinceCode ? City::where('province_code', $this->dlProvinceCode)->orderBy('name')->get() : collect(),
            'districtList' => $this->dlCityCode ? District::where('city_code', $this->dlCityCode)->orderBy('name')->get() : collect(),
            'villageList' => $this->dlDistrictCode ? Village::where('district_code', $this->dlDistrictCode)->orderBy('name')->get() : collect(),
        ];
    }
} ?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Pelayanan Radiologi</flux:heading>
        @if(in_array(auth()->user()->role, ['admin', 'radiologist']))
            <flux:button variant="primary" color="blue" icon="plus" wire:click="openTambahOrderModal">
                Tambah Order
            </flux:button>
        @endif
    </div>

    {{-- Filter --}}
    <flux:card class="space-y-4" style="border-left: 3px solid #408BFF">
        <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 text-sm">Ringkasan Tindakan Radiologi</h3>

        {{-- Baris 1: 5 Kolom Filter --}}
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <flux:field>
                <flux:label>Tgl. Order Awal</flux:label>
                <flux:input type="date" wire:model.live="tglAwal" />
            </flux:field>
            <flux:field>
                <flux:label>Tgl. Order Akhir</flux:label>
                <flux:input type="date" wire:model.live="tglAkhir" />
            </flux:field>
            <flux:field>
                <flux:label>Lokasi Asal</flux:label>
                <flux:select wire:model.live="lokasiAsal">
                    <flux:select.option value="">- Semua Lokasi -</flux:select.option>
                    @foreach($poliList as $poli)
                        <flux:select.option value="{{ $poli->id }}">{{ $poli->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Status Periksa</flux:label>
                <flux:select wire:model.live="statusPeriksa">
                    <flux:select.option value="">- Semua Status -</flux:select.option>
                    <flux:select.option value="order">Order</flux:select.option>
                    <flux:select.option value="datang">Datang</flux:select.option>
                    <flux:select.option value="valid">Valid</flux:select.option>
                    <flux:select.option value="proses">Proses</flux:select.option>
                    <flux:select.option value="selesai">Selesai</flux:select.option>
                    <flux:select.option value="serah">Serah</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Tipe Order</flux:label>
                <flux:select wire:model.live="tipeOrder">
                    <flux:select.option value="">- Semua Tipe -</flux:select.option>
                    <flux:select.option value="reguler">REGULER</flux:select.option>
                    <flux:select.option value="cito">CITO</flux:select.option>
                </flux:select>
            </flux:field>
        </div>

        {{-- Baris 2: Search Filter di Bawah --}}
        <div class="flex items-end gap-2">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>No.RM / Nama Pasien</flux:label>
                <flux:input wire:model.live.debounce.400ms="cariPasien" placeholder="Cari nama atau No. RM..." icon="magnifying-glass" />
            </flux:field>
            <flux:field>
                <flux:label class="opacity-0">.</flux:label>
                <flux:button variant="ghost" icon="x-mark" wire:click="resetFilter">Batal</flux:button>
            </flux:field>
        </div>
    </flux:card>

    {{-- Tabel --}}
    <flux:card>
        <div class="flex items-center gap-2 mb-3 text-sm">
            <span>Tampilkan</span>
            <flux:select wire:model.live="perPage" size="sm" class="w-20">
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="30">30</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
            <span>entri</span>
        </div>

        <flux:table container:class="max-h-[36rem]" :paginate="$tindakanList">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column class="w-10 pl-4 text-center">No</flux:table.column>
                <flux:table.column class="w-28">Aksi</flux:table.column>
                <flux:table.column>Tgl. Order</flux:table.column>
                <flux:table.column>No. Order</flux:table.column>
                <flux:table.column>No. RM</flux:table.column>
                <flux:table.column>Nama Pasien</flux:table.column>
                <flux:table.column class="min-w-[220px]">Item Pemeriksaan</flux:table.column>
                <flux:table.column class="text-right">Nominal</flux:table.column>
                <flux:table.column>Lokasi Asal</flux:table.column>
                <flux:table.column>Dokter Pengirim</flux:table.column>
                <flux:table.column>Penjamin</flux:table.column>
                <flux:table.column class="text-center">Tipe Order</flux:table.column>
                <flux:table.column class="text-center">St. Periksa</flux:table.column>
                <flux:table.column class="text-center">St. Diterima</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($tindakanList as $i => $t)
                    <flux:table.row :key="$t->id">
                        <flux:table.cell class="pl-4">{{ $tindakanList->firstItem() + $i }}</flux:table.cell>

                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button size="sm" variant="ghost" icon:trailing="chevron-down">Aksi</flux:button>
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" icon:variant="outline" href="{{ route('radiology.pemeriksaan.show', $t) }}" wire:navigate>
                                        Periksa Pasien
                                    </flux:menu.item>
                                    <flux:menu.item icon="check-circle" icon:variant="outline" wire:click="openDiterimaModal({{ $t->id }})">
                                        Diterima
                                    </flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" icon:variant="outline" variant="danger" wire:click="confirmDelete({{ $t->id }})">
                                        Hapus Order
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>

                        <flux:table.cell>{{ $t->created_at->format('d-m-Y H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $t->no_order ?: (now()->format('ymd') . str_pad((string)$t->id, 6, '0', STR_PAD_LEFT)) }}</flux:table.cell>
                        <flux:table.cell>{{ $t->patient->mrn }}</flux:table.cell>
                        <flux:table.cell>
                            <div>{{ $t->patient->name }}</div>
                            <div class="text-xs text-zinc-400">{{ $t->patient->date_of_birth?->age }} th</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $t->detailTindakan->pluck('examinationType.name')->implode(', ') ?: '-' }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            Rp {{ number_format($t->detailTindakan->sum('price'), 0, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $t->pelayanan->masterPoli->name ?? '-' }}</flux:cell>
                        <flux:table.cell>{{ $t->referring_doctor_name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $t->registrasi->jenis_pasien ?? 'UMUM' }}</flux:table.cell>
                        <flux:table.cell class="text-center">
                            <flux:badge size="sm" :color="strtolower($t->tipe_order ?? 'reguler') === 'cito' ? 'red' : 'blue'">
                                {{ strtoupper($t->tipe_order ?? 'REGULER') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            <flux:badge :color="match($t->status) {
                                'selesai', 'serah' => 'green',
                                'proses', 'valid' => 'yellow',
                                default => 'red',
                            }">
                                {{ ucfirst($t->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-center">
                            <flux:badge :color="match($t->status_diterima) {
                                'diterima' => 'green',
                                'konfirmasi' => 'yellow',
                                default => 'red',
                            }">
                                {{ str_replace('_', ' ', ucfirst($t->status_diterima)) }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="14" class="text-center text-zinc-400">
                            Tidak ada data yang tersedia pada tabel ini.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- MODAL: Tambah Order --}}
    <flux:modal name="tambah-order-modal" class="!max-w-5xl !w-full" style="max-width: 66rem; width: 100%;">
        <div class="space-y-4 p-2">
            <flux:heading size="lg">Tambah Order</flux:heading>

            {{-- Tabs --}}
            <div class="border-b border-zinc-200 dark:border-zinc-700 flex gap-2">
                <button type="button" wire:click="$set('activeModalTab', 'kunjungan')" class="px-4 py-2.5 text-xs font-semibold flex items-center gap-2 border-b-2 transition-colors {{ $activeModalTab === 'kunjungan' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                    <x-heroicon-o-user-group class="size-4" /> KUNJUNGAN
                </button>
                <button type="button" wire:click="$set('activeModalTab', 'datang_langsung')" class="px-4 py-2.5 text-xs font-semibold flex items-center gap-2 border-b-2 transition-colors {{ $activeModalTab === 'datang_langsung' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                    <x-heroicon-o-pencil-square class="size-4" /> DATANG LANGSUNG
                </button>
            </div>

            {{-- TAB 1: KUNJUNGAN --}}
            @if($activeModalTab === 'kunjungan')
                <div class="space-y-4 pt-1">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-xs">
                            <span>Tampilkan</span>
                            <flux:select wire:model.live="perPageKunjungan" size="sm" class="w-20">
                                <flux:select.option value="10">10</flux:select.option>
                                <flux:select.option value="25">25</flux:select.option>
                                <flux:select.option value="50">50</flux:select.option>
                            </flux:select>
                            <span>entri</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span>Cari:</span>
                            <flux:input wire:model.live.debounce.400ms="searchKunjungan" size="sm" class="w-56" placeholder="No.Reg / No.RM / Nama" />
                        </div>
                    </div>

                    <flux:table container:class="max-h-[26rem]" :paginate="$kunjunganList">
                        <flux:table.columns sticky class="bg-white dark:bg-zinc-900 text-xs">
                            <flux:table.column class="w-8 pl-2 text-center">NO</flux:table.column>
                            <flux:table.column>NO.REG</flux:table.column>
                            <flux:table.column>NO.RM</flux:table.column>
                            <flux:table.column>NAMA PASIEN</flux:table.column>
                            <flux:table.column>PENJAMIN</flux:table.column>
                            <flux:table.column>LOKASI PELAYANAN</flux:table.column>
                            <flux:table.column>DPJP</flux:table.column>
                            <flux:table.column>TGL.REG</flux:table.column>
                            <flux:table.column class="text-center">PILIH</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @forelse($kunjunganList as $kIndex => $kunjungan)
                                <flux:table.row :key="$kunjungan->id" class="text-xs">
                                    <flux:table.cell class="pl-2 text-center">{{ $kunjunganList->firstItem() + $kIndex }}</flux:table.cell>
                                    <flux:table.cell class="font-mono">{{ $kunjungan->no_registrasi }}</flux:table.cell>
                                    <flux:table.cell class="font-mono">{{ $kunjungan->patient->mrn ?? '-' }}</flux:table.cell>
                                    <flux:table.cell class="font-medium">{{ $kunjungan->patient->name ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>{{ $kunjungan->jenis_pasien ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>{{ $kunjungan->pelayanan->first()?->masterPoli?->name ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>{{ $kunjungan->pelayanan->first()?->dpjp?->name ?? '-' }}</flux:table.cell>
                                    <flux:table.cell>{{ $kunjungan->tanggal_registrasi?->format('d-m-Y H:i:s') }}</flux:table.cell>
                                    <flux:table.cell class="text-center">
                                        <flux:button size="xs" variant="primary" color="blue" wire:click="selectKunjungan({{ $kunjungan->id }})">
                                            Pilih &raquo;
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="9" class="text-center text-zinc-400 py-4">
                                        Tidak ada data kunjungan yang tersedia.
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif

            {{-- TAB 2: DATANG LANGSUNG --}}
            @if($activeModalTab === 'datang_langsung')
                <form wire:submit="saveDatangLangsung" class="space-y-5 pt-1 text-xs">
                    <table class="w-full border-collapse text-xs table-fixed">
                        <colgroup>
                            <col style="width: 25%;" />
                            <col style="width: 40%;" />
                            <col style="width: 35%;" />
                        </colgroup>
                        <tbody>
                            {{-- Tgl. Registrasi --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Tgl. Registrasi <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:input value="{{ $dlTglRegistrasi }}" readonly disabled size="sm" class="w-full" />
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Penjamin --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Penjamin <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model="dlPenjamin" size="sm" class="w-full">
                                        @foreach(\App\Models\Registrasi::JENIS_PASIEN_OPTIONS as $penjaminOpt)
                                            <flux:select.option value="{{ $penjaminOpt }}">{{ strtoupper($penjaminOpt) }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Punya No.RM ? --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Punya No.RM ? <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <div class="flex items-center gap-6">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" wire:model.live="dlPunyaNoRm" value="ya" class="text-blue-600 focus:ring-blue-500">
                                            <span>Ya</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="radio" wire:model.live="dlPunyaNoRm" value="tidak" class="text-blue-600 focus:ring-blue-500">
                                            <span>Tidak</span>
                                        </label>
                                    </div>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- No. Rekammedis (Ditampilkan jika Punya No.RM = ya) --}}
                            @if($dlPunyaNoRm === 'ya')
                                <tr>
                                    <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                        No. Rekammedis <span class="text-rose-500">*</span>
                                    </td>
                                    <td class="py-2.5 px-2 align-middle">
                                        <div class="flex items-center gap-2">
                                            <flux:input wire:model="dlSearchMrn" size="sm" placeholder="No. RM..." class="flex-1" />
                                            <flux:button size="sm" variant="filled" icon="magnifying-glass" icon:variant="outline" wire:click="searchPatientByMrn">Cari</flux:button>
                                        </div>
                                    </td>
                                    <td class="py-2.5 pl-4 align-middle"></td>
                                </tr>
                            @endif

                            {{-- NIK --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    NIK <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:input wire:model="dlNik" size="sm" placeholder="Nomor Induk Kependudukan" class="w-full" />
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Nama Pasien --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Nama Pasien <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <div class="flex items-center gap-2">
                                        <flux:select wire:model="dlTitle" size="sm" class="w-20">
                                            <flux:select.option value="Tn">Tn</flux:select.option>
                                            <flux:select.option value="Ny">Ny</flux:select.option>
                                            <flux:select.option value="An">An</flux:select.option>
                                            <flux:select.option value="Sdr">Sdr</flux:select.option>
                                            <flux:select.option value="Bpn">Bpn</flux:select.option>
                                        </flux:select>
                                        <flux:input wire:model="dlName" size="sm" placeholder="Nama Pasien Lengkap" class="flex-1" />
                                    </div>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Jenis Kelamin --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Jenis Kelamin <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model="dlGender" size="sm" class="w-full">
                                        <flux:select.option value="L">Laki-laki</flux:select.option>
                                        <flux:select.option value="P">Perempuan</flux:select.option>
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Tempat Lahir --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Tempat Lahir <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:input wire:model="dlTempatLahir" size="sm" placeholder="Kota Kelahiran" class="w-full" />
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Tanggal Lahir & Umur --}}
                            @php
                                $ageYears = 0;
                                $ageMonths = 0;
                                $ageDays = 0;

                                if (!empty($dlDateOfBirth)) {
                                    try {
                                        $dob = \Carbon\Carbon::parse($dlDateOfBirth);
                                        $now = now();
                                        $ageYears = (int) $dob->diffInYears($now);
                                        $ageMonths = (int) $dob->copy()->addYears($ageYears)->diffInMonths($now);
                                        $ageDays = (int) $dob->copy()->addYears($ageYears)->addMonths($ageMonths)->diffInDays($now);
                                    } catch (\Throwable $e) {}
                                }
                            @endphp
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Tanggal Lahir <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:input type="date" max="{{ date('Y-m-d') }}" wire:model.live="dlDateOfBirth" size="sm" class="w-full" />
                                </td>
                                {{-- Kolom 3: Umur (Thn, Bln, Hr) --}}
                                <td class="py-2.5 pl-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                            Umur (Thn, Bln, Hr) <span class="text-rose-500">*</span>
                                        </span>
                                        <flux:input value="{{ !empty($dlDateOfBirth) ? (int)$ageYears : '' }}" readonly disabled size="sm" class="w-12 text-center" />
                                        <flux:input value="{{ !empty($dlDateOfBirth) ? (int)$ageMonths : '' }}" readonly disabled size="sm" class="w-12 text-center" />
                                        <flux:input value="{{ !empty($dlDateOfBirth) ? (int)$ageDays : '' }}" readonly disabled size="sm" class="w-12 text-center" />
                                    </div>
                                </td>
                            </tr>

                            {{-- No. Handphone --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    No. Handphone <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:input wire:model="dlPhone" size="sm" placeholder="08xxxxxxxxxx" class="w-full" />
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Asal Pasien --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Asal Pasien <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model="dlAsalPasien" size="sm" class="w-full">
                                        @foreach(\App\Models\Registrasi::ASAL_PASIEN_OPTIONS as $asalOpt)
                                            <flux:select.option value="{{ $asalOpt }}">{{ $asalOpt }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Dokter --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Dokter <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model="dlDokterName" size="sm" class="w-full">
                                        <flux:select.option value="">- Pilih Dokter -</flux:select.option>
                                        @foreach($dokterList as $doc)
                                            <flux:select.option value="{{ $doc->name }}">{{ $doc->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Section Divider: Alamat Sesuai Identitas --}}
                            <tr>
                                <td colspan="3" class="pt-6 pb-3">
                                    <div class="border-t border-dashed border-zinc-200 dark:border-zinc-700 pt-3">
                                        <h4 class="font-semibold text-xs text-zinc-800 dark:text-zinc-200">Alamat Sesuai Identitas</h4>
                                        <p class="text-[10px] text-sky-600 dark:text-sky-400 mt-0.5">*) RT, RW, Kode Pos, Wilayah Provinsi s/d Kelurahan tidak perlu diisi di kolom alamat</p>
                                    </div>
                                </td>
                            </tr>

                            {{-- Alamat & RT/RW/Pos (Berada di bawah Alamat) --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-top font-medium text-zinc-700 dark:text-zinc-300 pt-3">
                                    Alamat <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle" colspan="2">
                                    <div class="space-y-2">
                                        <flux:input wire:model="dlAddress" size="sm" placeholder="Jalan / Perumahan / No. Rumah" class="w-full max-w-xl" />
                                        <div class="flex items-center gap-3 pt-1">
                                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">RT</span>
                                            <flux:input wire:model="dlRt" size="sm" class="w-16 text-center" />
                                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 ml-2">RW</span>
                                            <flux:input wire:model="dlRw" size="sm" class="w-16 text-center" />
                                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 ml-2">Pos</span>
                                            <flux:input wire:model="dlKodePos" size="sm" class="w-20 text-center" />
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- Negara --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Negara <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model="dlNegara" size="sm" class="w-full">
                                        <flux:select.option value="INDONESIA">INDONESIA</flux:select.option>
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Provinsi --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Provinsi <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model.live="dlProvinceCode" size="sm" class="w-full">
                                        <flux:select.option value="">- Pilih -</flux:select.option>
                                        @foreach($provinceList as $prov)
                                            <flux:select.option value="{{ $prov->code }}">{{ $prov->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Kabupaten/Kota --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Kabupaten/Kota <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model.live="dlCityCode" size="sm" class="w-full">
                                        <flux:select.option value="">- Pilih -</flux:select.option>
                                        @foreach($cityList as $city)
                                            <flux:select.option value="{{ $city->code }}">{{ $city->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Kecamatan --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Kecamatan <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model.live="dlDistrictCode" size="sm" class="w-full">
                                        <flux:select.option value="">- Pilih -</flux:select.option>
                                        @foreach($districtList as $dist)
                                            <flux:select.option value="{{ $dist->code }}">{{ $dist->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>

                            {{-- Kelurahan --}}
                            <tr>
                                <td class="py-2.5 pr-4 align-middle font-medium text-zinc-700 dark:text-zinc-300">
                                    Kelurahan <span class="text-rose-500">*</span>
                                </td>
                                <td class="py-2.5 px-2 align-middle">
                                    <flux:select wire:model="dlVillageCode" size="sm" class="w-full">
                                        <flux:select.option value="">- Pilih -</flux:select.option>
                                        @foreach($villageList as $vill)
                                            <flux:select.option value="{{ $vill->code }}">{{ $vill->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </td>
                                <td class="py-2.5 pl-4 align-middle"></td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Buttons --}}
                    <div class="flex justify-center gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <flux:button type="submit" variant="primary" color="blue" icon="document-check">
                            Simpan
                        </flux:button>
                        <flux:modal.close>
                            <flux:button variant="ghost" icon="x-mark">
                                Batal
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>

    {{-- MODAL: Diterima --}}
    <flux:modal name="diterima-form" class="md:w-[32rem]">
        <form wire:submit="saveDiterima" class="space-y-4">
            <flux:heading size="lg">Konfirmasi Penerimaan</flux:heading>

            <div class="grid grid-cols-3 gap-3 items-center">
                <flux:label class="col-span-1">Tanggal & Jam</flux:label>
                <div class="col-span-2">
                    <flux:input type="datetime-local" wire:model="diterimaTanggalJam" />
                    <flux:error name="diterimaTanggalJam" />
                </div>

                <flux:label class="col-span-1">Penerima</flux:label>
                <div class="col-span-2">
                    <flux:input wire:model="diterimaPenerima" placeholder="Nama penerima" />
                    <flux:error name="diterimaPenerima" />
                </div>

                <flux:label class="col-span-1">Status</flux:label>
                <div class="col-span-2">
                    <flux:accent color="blue">
                        <flux:radio.group wire:model="diterimaStatus">
                            <flux:radio value="diterima" label="Diterima" />
                            <flux:radio value="belum_diterima" label="Belum Diterima" />
                            <flux:radio value="konfirmasi" label="Konfirmasi" />
                        </flux:radio.group>
                        
                        <flux:error name="diterimaStatus" />
                    </flux:accent>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" color="blue">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- MODAL: Hapus Order --}}
    <flux:modal name="delete-tindakan-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus Order?</flux:heading>
            <flux:text>Seluruh data pemeriksaan, hasil, dan gambar terkait order ini akan ikut terhapus permanen.</flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteOrder">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>

</div>