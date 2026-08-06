<?php

use App\Models\MasterPoli;
use App\Models\Patient;
use App\Models\Pelayanan;
use App\Models\Registrasi;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Opsi Dropdown Tetap
    public array $agamaOptions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Penghayat Kepercayaan', 'Lainnya'];
    public array $pernikahanOptions = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
    public array $pendidikanOptions = ['Tidak/Belum Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat', 'D1-D3', 'D4/S1', 'S2', 'S3'];
    public array $sukuOptions = ['Jawa', 'Sunda', 'Batak', 'Minangkabau', 'Bugis', 'Betawi', 'Banjar', 'Madura', 'Tidak Tahu', 'Lainnya'];

    // Cari Pasien
    public string $statusPasien = 'baru';
    public string $searchTerm = '';
    public string $search = '';
    public ?int $patientId = null;

    // Data Pasien - Identitas Dasar
    public string $nik = '';
    public string $name = '';
    public string $gender = '';
    public string $tempatLahir = '';
    public string $dateOfBirth = '';
    public string $phone = '';

    // Data Pasien - Alamat
    public string $address = '';
    public string $rt = '';
    public string $rw = '';
    public string $kodePos = '';
    public string $negara = 'Indonesia';
    public string $provinceCode = '';
    public string $cityCode = '';
    public string $districtCode = '';
    public string $villageCode = '';

    // Data Pasien - Sosial
    public string $agama = '';
    public string $suku = '';
    public string $pekerjaan = '';
    public string $bahasa = 'Indonesia';
    public string $statusPernikahan = '';
    public string $pendidikan = '';
    public string $namaIbuKandung = '';

    // Data Registrasi & Pelayanan
    public string $tanggalRegistrasi = '';
    public string $masterPoliId = '';
    public string $dpjpId = '';
    public string $alergi = '';
    public string $penyakitKhusus = '';
    public string $jenisPasien = '';
    public string $kelasLayanan = '';
    public string $asalPasien = '';

    public function mount(): void
    {
        $this->tanggalRegistrasi = now()->format('Y-m-d');
    }

    public function updatedStatusPasien(): void
    {
        $this->resetPatientForm();
    }

    public function updated($property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    public function updatedProvinceCode(): void
    {
        $this->reset(['cityCode', 'districtCode', 'villageCode']);
    }

    public function updatedCityCode(): void
    {
        $this->reset(['districtCode', 'villageCode']);
    }

    public function updatedDistrictCode(): void
    {
        $this->reset(['villageCode']);
    }

    protected function resetPatientForm(): void
    {
        $this->reset([
            'patientId', 'nik', 'name', 'gender', 'tempatLahir', 'dateOfBirth', 'phone',
            'address', 'rt', 'rw', 'kodePos', 'provinceCode', 'cityCode', 'districtCode', 'villageCode',
            'agama', 'suku', 'pekerjaan', 'statusPernikahan', 'pendidikan', 'namaIbuKandung', 'searchTerm',
        ]);
        $this->negara = 'Indonesia';
        $this->bahasa = 'Indonesia';
    }

    public function cariPasien(): void
    {
        if (blank($this->searchTerm)) {
            Flux::toast(heading: 'Gagal', text: 'Masukkan NIK atau No. RM terlebih dahulu.', variant: 'danger');
            return;
        }

        $patient = Patient::where('nik', $this->searchTerm)
            ->orWhere('mrn', $this->searchTerm)
            ->first();

        if (! $patient) {
            Flux::toast(heading: 'Tidak Ditemukan', text: 'Pasien tidak ditemukan. Periksa kembali NIK/No. RM, atau pilih status "Baru".', variant: 'danger');
            $this->patientId = null;
            return;
        }

        $this->patientId = $patient->id;
        $this->nik = $patient->nik ?? '';
        $this->name = $patient->name;
        $this->gender = $patient->gender;
        $this->tempatLahir = $patient->tempat_lahir ?? '';
        $this->dateOfBirth = $patient->date_of_birth->format('Y-m-d');
        $this->phone = $patient->phone ?? '';
        $this->address = $patient->address ?? '';
        $this->rt = $patient->rt ?? '';
        $this->rw = $patient->rw ?? '';
        $this->kodePos = $patient->kode_pos ?? '';
        $this->negara = $patient->negara ?? 'Indonesia';
        $this->provinceCode = $patient->province_code ?? '';
        $this->cityCode = $patient->city_code ?? '';
        $this->districtCode = $patient->district_code ?? '';
        $this->villageCode = $patient->village_code ?? '';
        $this->agama = $patient->agama ?? '';
        $this->suku = $patient->suku ?? '';
        $this->pekerjaan = $patient->pekerjaan ?? '';
        $this->bahasa = $patient->bahasa ?? 'Indonesia';
        $this->statusPernikahan = $patient->status_pernikahan ?? '';
        $this->pendidikan = $patient->pendidikan ?? '';
        $this->namaIbuKandung = $patient->nama_ibu_kandung ?? '';

        Flux::toast(heading: 'Ditemukan', text: "Data pasien \"{$patient->name}\" berhasil dimuat.", variant: 'success');
    }

    public function save(): void
    {
        if ($this->statusPasien === 'lama' && ! $this->patientId) {
            Flux::toast(heading: 'Gagal', text: 'Silakan cari dan pilih pasien terlebih dahulu.', variant: 'danger');
            return;
        }

        $rules = [
            'masterPoliId' => 'required|exists:master_poli,id',
            'dpjpId' => 'required|exists:users,id',
            'tanggalRegistrasi' => 'required|date',
            'jenisPasien' => 'required|string',
            'kelasLayanan' => 'required|string',
            'asalPasien' => 'required|string',
        ];

        if ($this->statusPasien === 'baru') {
            $rules = array_merge($rules, [
                'nik' => 'required|string|max:20|unique:patients,nik',
                'name' => 'required|string|max:255',
                'gender' => 'required|in:L,P',
                'tempatLahir' => 'required|string|max:255',
                'dateOfBirth' => 'required|date',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'provinceCode' => 'nullable|exists:indonesia_provinces,code',
                'cityCode' => 'nullable|exists:indonesia_cities,code',
                'districtCode' => 'nullable|exists:indonesia_districts,code',
                'villageCode' => 'nullable|exists:indonesia_villages,code',
                'pekerjaan' => 'required|string',
                'statusPernikahan' => 'required|string',
                'pendidikan' => 'required|string',
                'namaIbuKandung' => 'required|string|max:255',
            ]);
        }

        $validated = $this->validate($rules);

        try {
            DB::transaction(function () use ($validated) {
                if ($this->statusPasien === 'baru') {
                    $patient = Patient::create([
                        'nik' => $validated['nik'],
                        'name' => $validated['name'],
                        'gender' => $validated['gender'],
                        'tempat_lahir' => $validated['tempatLahir'],
                        'date_of_birth' => $validated['dateOfBirth'],
                        'phone' => $validated['phone'],
                        'address' => $validated['address'],
                        'rt' => $this->rt ?: null,
                        'rw' => $this->rw ?: null,
                        'kode_pos' => $this->kodePos ?: null,
                        'negara' => $this->negara,
                        'province_code' => $validated['provinceCode'] ?: null,
                        'city_code' => $validated['cityCode'] ?: null,
                        'district_code' => $validated['districtCode'] ?: null,
                        'village_code' => $validated['villageCode'] ?: null,
                        'agama' => $this->agama ?: null,
                        'suku' => $this->suku ?: null,
                        'pekerjaan' => $this->pekerjaan ?: null,
                        'bahasa' => $this->bahasa,
                        'status_pernikahan' => $validated['statusPernikahan'],
                        'pendidikan' => $validated['pendidikan'],
                        'nama_ibu_kandung' => $validated['namaIbuKandung'],
                    ]);
                } else {
                    $patient = Patient::findOrFail($this->patientId);
                }

                $registrasi = Registrasi::create([
                    'patient_id' => $patient->id,
                    'tanggal_registrasi' => $validated['tanggalRegistrasi'],
                    'alergi' => $this->alergi ?: null,
                    'penyakit_khusus' => $this->penyakitKhusus ?: null,
                    'jenis_pasien' => $validated['jenisPasien'],
                    'kelas_layanan' => $validated['kelasLayanan'],
                    'asal_pasien' => $validated['asalPasien'],
                ]);

                Pelayanan::create([
                    'registrasi_id' => $registrasi->id,
                    'master_poli_id' => $validated['masterPoliId'],
                    'dpjp_id' => $validated['dpjpId'],
                    'status' => 'pending',
                ]);
            });

            Flux::toast(heading: 'Berhasil', text: 'Registrasi dan pelayanan berhasil disimpan.', variant: 'success');
            $this->resetForm();
        } catch (\Throwable $e) {
            logger()->error('Gagal menyimpan registrasi: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan: ' . $e->getMessage(), variant: 'danger');
        }
    }

    protected function resetForm(): void
    {
        $this->resetPatientForm();
        $this->reset(['masterPoliId', 'dpjpId', 'alergi', 'penyakitKhusus', 'jenisPasien', 'kelasLayanan', 'asalPasien']);
        $this->statusPasien = 'baru';
        $this->tanggalRegistrasi = now()->format('Y-m-d');
    }

    public function with(): array
    {
        return [
            'poliList' => MasterPoli::all(),
            'dpjpList' => User::where('role', 'dokter')->orderBy('name')->get(),
            'provinceList' => Province::orderBy('name')->get(),
            'cityList' => $this->provinceCode
                ? City::where('province_code', $this->provinceCode)->orderBy('name')->get()
                : collect(),
            'districtList' => $this->cityCode
                ? District::where('city_code', $this->cityCode)->orderBy('name')->get()
                : collect(),
            'villageList' => $this->districtCode
                ? Village::where('district_code', $this->districtCode)->orderBy('name')->get()
                : collect(),
            'recentPelayanan' => Pelayanan::with(['registrasi.patient', 'masterPoli', 'dpjp'])
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereHas('registrasi.patient', function ($subPatient) {
                            $subPatient->where('name', 'ilike', "%{$this->search}%")
                                ->orWhere('mrn', 'ilike', "%{$this->search}%")
                                ->orWhere('nik', 'ilike', "%{$this->search}%");
                        })
                        ->orWhereHas('registrasi', function ($subRegistrasi) {
                            $subRegistrasi->where('no_registrasi', 'ilike', "%{$this->search}%");
                        })
                        ->orWhereHas('masterPoli', function ($subPoli) {
                            $subPoli->where('name', 'ilike', "%{$this->search}%");
                        })
                        ->orWhereHas('dpjp', function ($subDpjp) {
                            $subDpjp->where('name', 'ilike', "%{$this->search}%");
                        });
                    });
                })
                ->latest()
                ->paginate(10),
        ];
    }
} ?>

<div class="space-y-6">

    <flux:heading size="xl">Registrasi & Pelayanan</flux:heading>

    <flux:card style="border-left: 3px solid #408BFF">
        <flux:heading size="lg" class="mb-4">Status Pasien</flux:heading>

        <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4">
            <nav class="flex gap-1">
                @foreach(['baru' => 'Baru', 'lama' => 'Lama'] as $value => $label)
                    <button
                        type="button"
                        wire:click="$set('statusPasien', '{{ $value }}')"
                        class="px-4 py-2.5 border-b-2 font-medium text-sm transition-colors
                            {{ $statusPasien === $value
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        @if($statusPasien === 'lama')
            <div class="flex items-end gap-2 mb-4">
                <flux:field class="flex-1 max-w-sm">
                    <flux:label>NIK / No. RM</flux:label>
                    <flux:input wire:model="searchTerm" placeholder="Masukkan NIK atau No. RM" />
                </flux:field>
                <flux:field>
                    <flux:label class="opacity-0">.</flux:label>
                    <flux:button variant="primary" icon="magnifying-glass" wire:click="cariPasien" color="blue">Cari</flux:button>
                </flux:field>
            </div>
        @endif
    </flux:card>

    <form wire:submit="save">

        {{-- Identitas Dasar --}}
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Data Identitas</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label badge="Requiered">NIK</flux:label>
                    <flux:input wire:model="nik" :disabled="$statusPasien === 'lama'" placeholder="16 digit NIK" />
                    <flux:error name="nik" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label badge="Requiered">Nama Pasien</flux:label>
                    <flux:input wire:model="name" :disabled="$statusPasien === 'lama'" placeholder="Nama lengkap" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Jenis Kelamin</flux:label>
                    <flux:select wire:model="gender" :disabled="$statusPasien === 'lama'">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        <flux:select.option value="L">Laki-laki</flux:select.option>
                        <flux:select.option value="P">Perempuan</flux:select.option>
                    </flux:select>
                    <flux:error name="gender" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Tempat Lahir</flux:label>
                    <flux:input wire:model="tempatLahir" :disabled="$statusPasien === 'lama'" placeholder="Kota kelahiran" />
                    <flux:error name="tempatLahir" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Tanggal Lahir</flux:label>
                    <flux:input type="date" max="{{ date('Y-m-d') }}" wire:model="dateOfBirth" :disabled="$statusPasien === 'lama'" />
                    <flux:error name="dateOfBirth" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">No. Handphone</flux:label>
                    <flux:input wire:model="phone" :disabled="$statusPasien === 'lama'" placeholder="08xxxxxxxxxx" />
                    <flux:error name="phone" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Alamat --}}
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Alamat</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:field class="md:col-span-3">
                    <flux:label badge="Requiered">Alamat</flux:label>
                    <flux:textarea wire:model="address" :disabled="$statusPasien === 'lama'" rows="2" placeholder="Nama jalan, nomor rumah" />
                    <flux:error name="address" />
                </flux:field>

                <flux:field>
                    <flux:label>RT</flux:label>
                    <flux:input wire:model="rt" :disabled="$statusPasien === 'lama'" placeholder="001" />
                </flux:field>

                <flux:field>
                    <flux:label>RW</flux:label>
                    <flux:input wire:model="rw" :disabled="$statusPasien === 'lama'" placeholder="002" />
                </flux:field>

                <flux:field>
                    <flux:label>Kode Pos</flux:label>
                    <flux:input wire:model="kodePos" :disabled="$statusPasien === 'lama'" placeholder="53123" />
                </flux:field>

                <flux:field>
                    <flux:label>Negara</flux:label>
                    <flux:input wire:model="negara" :disabled="$statusPasien === 'lama'" />
                </flux:field>

                <flux:field>
                    <flux:label>Provinsi</flux:label>
                    <flux:select wire:model.live="provinceCode" :disabled="$statusPasien === 'lama'">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($provinceList as $p)
                            <flux:select.option value="{{ $p->code }}">{{ $p->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="provinceCode" />
                </flux:field>

                <flux:field>
                    <flux:label>Kabupaten/Kota</flux:label>
                    <flux:select wire:model.live="cityCode" :disabled="$statusPasien === 'lama' || !$provinceCode">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($cityList as $c)
                            <flux:select.option value="{{ $c->code }}">{{ $c->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="cityCode" />
                </flux:field>

                <flux:field>
                    <flux:label>Kecamatan</flux:label>
                    <flux:select wire:model.live="districtCode" :disabled="$statusPasien === 'lama' || !$cityCode">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($districtList as $d)
                            <flux:select.option value="{{ $d->code }}">{{ $d->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="districtCode" />
                </flux:field>

                <flux:field>
                    <flux:label>Kelurahan</flux:label>
                    <flux:select wire:model="villageCode" :disabled="$statusPasien === 'lama' || !$districtCode">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($villageList as $v)
                            <flux:select.option value="{{ $v->code }}">{{ $v->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="villageCode" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Data Sosial --}}
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Data Sosial & Keluarga</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Agama</flux:label>
                    <flux:select wire:model="agama" :disabled="$statusPasien === 'lama'">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($agamaOptions as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="agama" />
                </flux:field>

                <flux:field>
                    <flux:label>Suku</flux:label>
                    <flux:select wire:model="suku" :disabled="$statusPasien === 'lama'">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($sukuOptions as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Pekerjaan</flux:label>
                    <flux:input wire:model="pekerjaan" :disabled="$statusPasien === 'lama'" placeholder="misal: Wiraswasta" />
                </flux:field>

                <flux:field>
                    <flux:label>Bahasa</flux:label>
                    <flux:input wire:model="bahasa" :disabled="$statusPasien === 'lama'" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Status Pernikahan</flux:label>
                    <flux:select wire:model="statusPernikahan" :disabled="$statusPasien === 'lama'">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($pernikahanOptions as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="statusPernikahan" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Pendidikan</flux:label>
                    <flux:select wire:model="pendidikan" :disabled="$statusPasien === 'lama'">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($pendidikanOptions as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="pendidikan" />
                </flux:field>

                <flux:field class="md:col-span-3">
                    <flux:label badge="Requiered">Nama Ibu Kandung</flux:label>
                    <flux:input wire:model="namaIbuKandung" :disabled="$statusPasien === 'lama'" />
                    <flux:error name="namaIbuKandung" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Informasi Kunjungan --}}
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Informasi Kunjungan</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:field class="md:col-span-3">
                    <flux:label>Alergi</flux:label>
                    <flux:textarea wire:model="alergi" rows="2" placeholder="Kosongkan jika tidak ada alergi" />
                </flux:field>

                <flux:field class="md:col-span-3">
                    <flux:label>Penyakit Khusus</flux:label>
                    <flux:textarea wire:model="penyakitKhusus" rows="2" placeholder="Kosongkan jika tidak ada" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Jenis Pasien</flux:label>
                    <flux:select wire:model="jenisPasien">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach(\App\Models\Registrasi::JENIS_PASIEN_OPTIONS as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="jenisPasien" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Kelas Layanan</flux:label>
                    <flux:select wire:model="kelasLayanan">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach(\App\Models\Registrasi::KELAS_LAYANAN_OPTIONS as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="kelasLayanan" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Asal Pasien</flux:label>
                    <flux:select wire:model="asalPasien">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach(\App\Models\Registrasi::ASAL_PASIEN_OPTIONS as $opt)
                            <flux:select.option value="{{ $opt }}">{{ $opt }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="asalPasien" />
                </flux:field>
            </div>
        </flux:card>

        {{-- Registrasi & Pelayanan --}}
        <flux:card class="mb-6">
            <flux:heading size="lg" class="mb-4">Registrasi & Pelayanan</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Tgl. Registrasi</flux:label>
                    <flux:input type="date" max="{{ date('Y-m-d') }}" wire:model="tanggalRegistrasi" />
                    <flux:error name="tanggalRegistrasi" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">Poli Klinik</flux:label>
                    <flux:select wire:model="masterPoliId">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($poliList as $poli)
                            <flux:select.option value="{{ $poli->id }}">{{ $poli->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="masterPoliId" />
                </flux:field>

                <flux:field>
                    <flux:label badge="Requiered">DPJP</flux:label>
                    <flux:select wire:model="dpjpId">
                        <flux:select.option value="">- Pilih -</flux:select.option>
                        @foreach($dpjpList as $dokter)
                            <flux:select.option value="{{ $dokter->id }}">{{ $dokter->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="dpjpId" />
                </flux:field>
            </div>
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button type="submit" variant="primary" icon="check" color="blue">Simpan Registrasi</flux:button>
        </div>
    </form>

    <flux:card>
        <flux:heading size="lg" class="mb-4">Registrasi Terbaru</flux:heading>

        <div class="flex flex-wrap items-end gap-4 mb-4">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>Cari</flux:label>
                <flux:input wire:model.live.debounce.400ms="search" placeholder="Nama atau kode..." icon="magnifying-glass" />
            </flux:field>
        </div>

        <flux:table container:class="max-h-96" :paginate="$recentPelayanan">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Tanggal Registrasi</flux:table.column>
                <flux:table.column>No. Registrasi</flux:table.column>
                <flux:table.column>No. RM</flux:table.column>
                <flux:table.column>Nama Pasien</flux:table.column>
                <flux:table.column>Poli</flux:table.column>
                <flux:table.column>DPJP</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($recentPelayanan as $i => $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $recentPelayanan->firstItem() + $i }}</flux:table.cell>
                        <flux:table.cell>{{ $item->registrasi->tanggal_registrasi->format('d-m-Y H:i:s') }}</flux:table.cell>
                        <flux:table.cell>{{ $item->registrasi->no_registrasi }}</flux:table.cell>
                        <flux:table.cell>{{ $item->registrasi->patient->mrn }}</flux:table.cell>
                        <flux:table.cell>{{ $item->registrasi->patient->name }}</flux:table.cell>
                        <flux:table.cell>{{ $item->masterPoli->name }}</flux:table.cell>
                        <flux:table.cell>{{ $item->dpjp->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$item->status === 'completed' ? 'green' : 'zinc'">{{ $item->status }}</flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-400">Belum ada data registrasi.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

</div>