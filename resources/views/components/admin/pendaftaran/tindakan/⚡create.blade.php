<?php

use App\Models\ExaminationType;
use App\Models\JenisTindakan;
use App\Models\Registrasi;
use App\Models\Tindakan;
use App\Models\DetailTindakan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public $registrasiId;
    public $registrasi;

    // Form fields
    public $tanggalJamOrder;
    public $dokterPengirim = '';
    public $tipeOrder = 'reguler';
    public $clinicalNotes = '';
    public $searchPemeriksaan = '';
    
    // Selected examinations
    public $selectedExaminations = [];

    public function mount()
    {
        $this->registrasiId = request()->query('registrasi_id');
        
        if (!$this->registrasiId) {
            abort(404, 'Registrasi ID tidak ditemukan.');
        }

        $this->registrasi = Registrasi::with(['patient', 'pelayanan.dpjp'])->findOrFail($this->registrasiId);
        
        $this->tanggalJamOrder = now()->format('Y-m-d\TH:i');
        $this->dokterPengirim = $this->registrasi->pelayanan->first()?->dpjp?->name ?? '';
    }

    public function getDokterListProperty()
    {
        return User::where('role', 'dokter')->orderBy('name')->get();
    }

    public function simpan()
    {
        $this->validate([
            'tanggalJamOrder' => 'required|date',
            'dokterPengirim' => 'required|string|max:255',
            'tipeOrder' => 'required|in:reguler,cito',
            'selectedExaminations' => 'required|array|min:1',
        ], [
            'selectedExaminations.required' => 'Pilih minimal satu jenis pemeriksaan.',
            'selectedExaminations.min' => 'Pilih minimal satu jenis pemeriksaan.',
        ]);

        try {
            $createdTindakan = null;

            DB::transaction(function () use (&$createdTindakan) {
                $pelayanan = $this->registrasi->pelayanan->first();
                $radiologiId = JenisTindakan::where('name', 'Radiologi')->value('id');

                $createdTindakan = Tindakan::create([
                    'registrasi_id' => $this->registrasi->id,
                    'pelayanan_id' => $pelayanan?->id,
                    'patient_id' => $this->registrasi->patient_id,
                    'jenis_tindakan_id' => $radiologiId,
                    'referring_doctor_name' => $this->dokterPengirim,
                    'tipe_order' => $this->tipeOrder,
                    'clinical_notes' => $this->clinicalNotes,
                    'status' => 'order',
                    'created_at' => $this->tanggalJamOrder,
                ]);

                $examinations = ExaminationType::whereIn('id', $this->selectedExaminations)->get();
                foreach ($examinations as $exam) {
                    DetailTindakan::create([
                        'tindakan_id' => $createdTindakan->id,
                        'examination_type_id' => $exam->id,
                        'price' => $exam->price,
                    ]);
                }
            });

            Flux::toast(heading: 'Berhasil', text: 'Order Radiologi berhasil dibuat.', variant: 'success');
            return redirect()->route('radiology.pemeriksaan.show', $createdTindakan);
        } catch (\Throwable $e) {
            logger()->error('Gagal membuat order radiologi: ' . $e->getMessage(), ['exception' => $e]);
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan order: ' . $e->getMessage(), variant: 'danger');
        }
    }

    public function getGroupedExaminationsProperty()
    {
        $query = ExaminationType::query();
        
        if (!empty($this->searchPemeriksaan)) {
            $query->where('name', 'ilike', '%' . $this->searchPemeriksaan . '%')
                  ->orWhere('code', 'ilike', '%' . $this->searchPemeriksaan . '%');
        }
        
        return $query->get()->groupBy(function($item) {
            return $item->category ?: 'Lainnya';
        });
    }
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Form Order Radiologi</flux:heading>
            <flux:subheading>Lengkapi data order dan pilih jenis pemeriksaan</flux:subheading>
        </div>
        <flux:button variant="subtle" icon="arrow-left" href="{{ route('admin.pendaftaran.tindakan.index') }}" wire:navigate>
            Kembali
        </flux:button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Panel Kiri: Informasi Pasien --}}
        <div class="lg:col-span-1 space-y-6">
            <flux:card>
                <flux:heading size="md" class="mb-4">Informasi Pasien</flux:heading>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-zinc-500 mb-1">No. Rekam Medis</div>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $registrasi->patient->mrn ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 mb-1">Nama Pasien</div>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $registrasi->patient->name }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 mb-1">Tanggal Lahir / Umur</div>
                        <div class="font-medium text-zinc-900 dark:text-white">
                            {{ $registrasi->patient->date_of_birth?->format('d-m-Y') ?? '-' }} 
                            <span class="text-zinc-400">({{ $registrasi->patient->date_of_birth?->age ?? '-' }} th)</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-zinc-500 mb-1">Jenis Kelamin</div>
                        <div class="font-medium text-zinc-900 dark:text-white">
                            {{ $registrasi->patient->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                        </div>
                    </div>
                    
                    <flux:separator variant="subtle" class="my-4" />
                    
                    <div>
                        <div class="text-zinc-500 mb-1">No. Registrasi</div>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $registrasi->no_registrasi }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 mb-1">Penjamin / Jenis Pasien</div>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $registrasi->jenis_pasien ?? 'UMUM' }}</div>
                    </div>
                    <div>
                        <div class="text-zinc-500 mb-1">Asal Pelayanan</div>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $registrasi->pelayanan->first()?->masterPoli?->name ?? '-' }}</div>
                    </div>
                </div>
            </flux:card>
        </div>

        {{-- Panel Kanan: Form Order & Pilih Pemeriksaan --}}
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <flux:heading size="md" class="mb-4">Data Order Radiologi</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label badge="Required">Tgl & Jam Order</flux:label>
                        <flux:input type="datetime-local" wire:model="tanggalJamOrder" />
                        <flux:error name="tanggalJamOrder" />
                    </flux:field>
                    
                    <flux:field>
                        <flux:label badge="Required">Tipe Order</flux:label>
                        <flux:select wire:model="tipeOrder">
                            <flux:select.option value="reguler">REGULER</flux:select.option>
                            <flux:select.option value="cito">CITO</flux:select.option>
                        </flux:select>
                        <flux:error name="tipeOrder" />
                    </flux:field>
                    
                    <flux:field class="md:col-span-2">
                        <flux:label badge="Required">Dokter Pengirim</flux:label>
                        <flux:select wire:model="dokterPengirim" placeholder="Pilih Dokter...">
                            @foreach($this->dokterList as $dokter)
                                <flux:select.option value="{{ $dokter->name }}">{{ $dokter->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="dokterPengirim" />
                    </flux:field>
                    
                    <flux:field class="md:col-span-2">
                        <flux:label>Keterangan Klinis</flux:label>
                        <flux:textarea wire:model="clinicalNotes" placeholder="Isi dengan diagnosa atau klinis pasien..." rows="3" />
                        <flux:error name="clinicalNotes" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="md">Pilih Pemeriksaan</flux:heading>
                    <flux:input wire:model.live.debounce.300ms="searchPemeriksaan" icon="magnifying-glass" placeholder="Cari pemeriksaan..." size="sm" class="w-64" />
                </div>
                
                <flux:error name="selectedExaminations" class="mb-4" />

                <div class="max-h-[32rem] overflow-y-auto space-y-6 pr-2">
                    @forelse($this->groupedExaminations as $category => $examinations)
                        <div>
                            <div class="bg-zinc-100 dark:bg-zinc-800 px-3 py-2 rounded-lg font-semibold text-sm mb-3">
                                {{ strtoupper($category) }}
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-2">
                                @foreach($examinations as $exam)
                                <flux:accent color="blue">
                                    <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition-colors {{ in_array($exam->id, $selectedExaminations) ? 'bg-blue-50/50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50' }}">
                                        <flux:checkbox 
                                            wire:model="selectedExaminations"
                                            value="{{ $exam->id }}"
                                            class="mt-0.5" 
                                        />
                                        <div class="flex-1">
                                            <div class="text-sm font-medium {{ in_array($exam->id, $selectedExaminations) ? 'text-blue-700 dark:text-blue-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                                {{ $exam->name }}
                                            </div>
                                            <div class="text-xs text-zinc-500 mt-1">
                                                Rp {{ number_format($exam->price, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </label>
                                </flux:accent>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-zinc-500">
                            Pemeriksaan tidak ditemukan.
                        </div>
                    @endforelse
                </div>
            </flux:card>
            
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="subtle" href="{{ route('admin.pendaftaran.tindakan.index') }}" wire:navigate>Batal</flux:button>
                <flux:button variant="primary" color="blue" wire:click="simpan">Simpan Order</flux:button>
            </div>
        </div>
    </div>
</div>