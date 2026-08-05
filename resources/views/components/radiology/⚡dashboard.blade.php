<?php

use App\Models\DetailTindakan;
use App\Models\JenisTindakan;
use App\Models\MasterPoli;
use App\Models\Tindakan;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Filter: Tabel Detail Tindakan
    public string $tglAwal = '';
    public string $tglAkhir = '';
    public string $lokasiAsal = '';
    public string $statusPeriksa = '';
    public string $tipeOrder = '';
    public string $cariPasien = '';
    public int $perPageTindakan = 10;

    public function mount(): void
    {
        $this->tglAwal = now()->format('Y-m-d');
    }

    public function updated($property): void
    {
        if (in_array($property, ['tglAwal', 'tglAkhir', 'lokasiAsal', 'statusPeriksa', 'tipeOrder', 'cariPasien', 'perPageTindakan'])) {
            $this->resetPage('tindakan-page');
        }
    }

    public function resetFilterTindakan(): void
    {
        $this->reset(['tglAwal', 'tglAkhir', 'lokasiAsal', 'statusPeriksa', 'tipeOrder', 'cariPasien']);
        $this->tglAwal = now()->format('Y-m-d');
        $this->resetPage('tindakan-page');
    }

    public function with(): array
    {
        $tindakanQuery = DetailTindakan::query()
            ->with(['tindakan.patient', 'tindakan.pelayanan.masterPoli', 'tindakan.jenisTindakan', 'examinationType']);

        if ($this->tglAwal) {
            $tindakanQuery->whereDate('created_at', '>=', $this->tglAwal);
        }
        if ($this->tglAkhir) {
            $tindakanQuery->whereDate('created_at', '<=', $this->tglAkhir);
        }
        if ($this->lokasiAsal) {
            $tindakanQuery->whereHas('tindakan.pelayanan', fn ($q) => $q->where('master_poli_id', $this->lokasiAsal));
        }
        if ($this->statusPeriksa) {
            $tindakanQuery->whereHas('tindakan', function ($q) {
                $q->where('status', $this->statusPeriksa);
            });
        }
        if ($this->tipeOrder) {
            $tindakanQuery->whereHas('tindakan', fn ($q) => $q->where('jenis_tindakan_id', $this->tipeOrder));
        }
        if ($this->cariPasien) {
            $tindakanQuery->whereHas('tindakan.patient', function ($q) {
                $q->where('name', 'ilike', "%{$this->cariPasien}%")
                  ->orWhere('mrn', 'ilike', "%{$this->cariPasien}%");
            });
        }

        // Card 1: Total Pemeriksaan (semua Tindakan)
        $totalTindakan = Tindakan::count();

        // Card 2: Pemeriksaan Hari Ini
        $pemeriksaanHariIni = Tindakan::whereDate('created_at', today())->count();
        $pemeriksaanKemarin = Tindakan::whereDate('created_at', today()->subDay())->count();
        $diffPemeriksaan = $pemeriksaanHariIni - $pemeriksaanKemarin;

        // Card 3: Status Pemeriksaan
        $pendingCount  = Tindakan::whereIn('status', ['order', 'datang', 'valid'])->count();
        $prosesCount   = Tindakan::where('status', 'proses')->count();
        $selesaiCount  = Tindakan::whereIn('status', ['selesai', 'serah'])->count();

        $pendingPercent  = $totalTindakan > 0 ? round(($pendingCount  / $totalTindakan) * 100, 1) : 0;
        $prosesPercent   = $totalTindakan > 0 ? round(($prosesCount   / $totalTindakan) * 100, 1) : 0;
        $selesaiPercent  = $totalTindakan > 0 ? round(($selesaiCount  / $totalTindakan) * 100, 1) : 0;

        return [
            'totalTindakan'          => $totalTindakan,
            'pemeriksaanHariIni'     => $pemeriksaanHariIni,
            'diffPemeriksaan'        => $diffPemeriksaan,
            'pendingCount'           => $pendingCount,
            'pendingPercent'         => $pendingPercent,
            'prosesCount'            => $prosesCount,
            'prosesPercent'          => $prosesPercent,
            'selesaiCount'           => $selesaiCount,
            'selesaiPercent'         => $selesaiPercent,
            'recentDetailTindakan'   => $tindakanQuery->latest()->paginate($this->perPageTindakan, pageName: 'tindakan-page'),
            'poliList'               => MasterPoli::all(),
            'jenisTindakanList'      => JenisTindakan::all(),
        ];
    }
} ?>

<div class="space-y-6">

    {{-- ── Header ── --}}
    <x-dashboard-header subheading="Berikut ringkasan pemeriksaan radiologi Anda hari ini." />

    {{-- ── Summary Cards ── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Card 1: Total Pemeriksaan --}}
        <flux:card style="border-left: 3px solid #408BFF;" class="flex flex-col justify-between">
            <div>
                <flux:subheading>Total Pemeriksaan</flux:subheading>
                <flux:heading size="xl" class="mt-1">{{ number_format($totalTindakan, 0, ',', '.') }}</flux:heading>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                <span>Seluruh data pemeriksaan</span>
            </div>
        </flux:card>

        {{-- Card 2: Pemeriksaan Hari Ini --}}
        <flux:card style="border-left: 3px solid #408BFF;" class="flex flex-col justify-between">
            <div>
                <flux:subheading>Pemeriksaan Hari Ini</flux:subheading>
                <flux:heading size="xl" class="mt-1">{{ number_format($pemeriksaanHariIni, 0, ',', '.') }}</flux:heading>
            </div>
            <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                @if($diffPemeriksaan > 0)
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">+{{ $diffPemeriksaan }} dibanding kemarin</span>
                @elseif($diffPemeriksaan < 0)
                    <span class="text-rose-600 dark:text-rose-400 font-medium">{{ $diffPemeriksaan }} dibanding kemarin</span>
                @else
                    <span>Sama dengan kemarin</span>
                @endif
            </div>
        </flux:card>

        {{-- Card 3: Status Pemeriksaan (spans 2 columns) --}}
        <flux:card style="border-left: 3px solid #408BFF;" class="md:col-span-2 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <flux:subheading class="font-semibold">Status Pemeriksaan</flux:subheading>
                    <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400 font-medium">Total: {{ number_format($totalTindakan, 0, ',', '.') }}</flux:text>
                </div>

                <div class="space-y-3">
                    <flux:field>
                        <flux:label>
                            Pending
                            <x-slot:trailing>
                                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                    {{ $pendingCount }} ({{ $pendingPercent }}%)
                                </span>
                            </x-slot:trailing>
                        </flux:label>
                        <flux:progress value="{{ $pendingPercent }}" color="amber" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Sedang Diproses
                            <x-slot:trailing>
                                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                    {{ $prosesCount }} ({{ $prosesPercent }}%)
                                </span>
                            </x-slot:trailing>
                        </flux:label>
                        <flux:progress value="{{ $prosesPercent }}" color="sky" />
                    </flux:field>

                    <flux:field>
                        <flux:label>
                            Selesai
                            <x-slot:trailing>
                                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                                    {{ $selesaiCount }} ({{ $selesaiPercent }}%)
                                </span>
                            </x-slot:trailing>
                        </flux:label>
                        <flux:progress value="{{ $selesaiPercent }}" color="emerald" />
                    </flux:field>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- ── Ringkasan Tindakan Radiologi ── --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Ringkasan Tindakan Radiologi</flux:heading>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
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
                    @foreach($jenisTindakanList as $jenis)
                        <flux:select.option value="{{ $jenis->id }}">{{ $jenis->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <div class="flex items-end gap-2 mb-4">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>No.RM / Nama Pasien</flux:label>
                <flux:input wire:model.live.debounce.400ms="cariPasien" placeholder="Cari nama atau No. RM..." icon="magnifying-glass" />
            </flux:field>
            <flux:field>
                <flux:label class="opacity-0">.</flux:label>
                <flux:button variant="ghost" wire:click="resetFilterTindakan" icon="x-mark">Batal</flux:button>
            </flux:field>
        </div>

        <div class="flex items-center gap-2 mb-3 text-sm">
            <span>Tampilkan</span>
            <flux:select wire:model.live="perPageTindakan" size="sm" class="w-20">
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="30">30</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
            <span>entri</span>
        </div>

        <flux:table container:class="max-h-96" :paginate="$recentDetailTindakan">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
                <flux:table.column>Tgl. Order</flux:table.column>
                <flux:table.column>No. Order</flux:table.column>
                <flux:table.column>No. RM</flux:table.column>
                <flux:table.column>Nama Pasien</flux:table.column>
                <flux:table.column>Item Pemeriksaan</flux:table.column>
                <flux:table.column>Nominal</flux:table.column>
                <flux:table.column>Lokasi Asal</flux:table.column>
                <flux:table.column>Dokter Pengirim</flux:table.column>
                <flux:table.column>Tipe Order</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($recentDetailTindakan as $i => $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $recentDetailTindakan->firstItem() + $i }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" href="{{ route('radiology.pemeriksaan.show', $item->tindakan) }}" wire:navigate>
                                <x-heroicon-o-eye class="size-4" />
                            </flux:button>
                        </flux:table.cell>
                        <flux:table.cell>{{ $item->created_at->format('d-m-Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $item->no_order }}</flux:table.cell>
                        <flux:table.cell>{{ $item->tindakan->patient->mrn ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->tindakan->patient->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->examinationType->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>Rp {{ number_format($item->price, 0, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>{{ $item->tindakan->pelayanan->masterPoli->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->tindakan->referring_doctor_name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->tindakan->jenisTindakan->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="match($item->tindakan->status) {
                                'order'   => 'zinc',
                                'datang'  => 'sky',
                                'valid'   => 'blue',
                                'proses'  => 'yellow',
                                'selesai' => 'green',
                                'serah'   => 'purple',
                                default   => 'gray',
                            }">
                                {{ ucfirst($item->tindakan->status) }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="12" class="text-center text-zinc-400">
                            Tidak ada data yang tersedia pada tabel ini.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

</div>
