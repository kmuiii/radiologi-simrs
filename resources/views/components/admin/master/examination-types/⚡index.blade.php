<?php

use App\Models\ExaminationType;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Filter
    public string $search = '';
    public int $perPage = 10;

    // Form State
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $price = '';

    // Delete State
    public ?int $deletingId = null;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function resetFilter(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'name', 'code', 'price']);
        Flux::modal('examination-type-form')->show();
    }

    public function openEditModal(int $id): void
    {
        $item = ExaminationType::findOrFail($id);

        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->code = $item->code ?? '';
        $this->price = (string) $item->price;

        Flux::modal('examination-type-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:examination_types,code' . ($this->editingId ? ",{$this->editingId}" : ''),
            'price' => 'required|numeric|min:0',
        ]);

        try {
            if ($this->editingId) {
                $item = ExaminationType::findOrFail($this->editingId);
                $item->update($validated);

                Flux::toast(heading: 'Berhasil', text: "Data \"{$item->name}\" berhasil diperbarui.", variant: 'success');
            } else {
                $item = ExaminationType::create($validated);

                Flux::toast(heading: 'Berhasil', text: "Jenis pemeriksaan \"{$item->name}\" berhasil ditambahkan.", variant: 'success');
            }

            Flux::modal('examination-type-form')->close();
            $this->reset(['editingId', 'name', 'code', 'price']);
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data.', variant: 'danger');
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-examination-type-confirm')->show();
    }

    public function delete(): void
    {
        try {
            $item = ExaminationType::findOrFail($this->deletingId);

            if ($item->detailTindakan()->exists()) {
                Flux::toast(
                    heading: 'Gagal',
                    text: "Tidak dapat menghapus \"{$item->name}\" karena masih digunakan pada data Detail Tindakan.",
                    variant: 'danger',
                );
                Flux::modal('delete-examination-type-confirm')->close();
                return;
            }

            $name = $item->name;
            $item->delete();

            Flux::toast(heading: 'Berhasil', text: "\"{$name}\" berhasil dihapus.", variant: 'success');
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menghapus data.', variant: 'danger');
        }

        Flux::modal('delete-examination-type-confirm')->close();
        $this->deletingId = null;
    }

    public function with(): array
    {
        return [
            'examinationTypes' => ExaminationType::query()
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'ilike', "%{$this->search}%")
                            ->orWhere('code', 'ilike', "%{$this->search}%");
                    });
                })
                ->orderBy('name')
                ->paginate($this->perPage),
        ];
    }
} ?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Jenis Pemeriksaan</flux:heading>
        <flux:button variant="primary" icon="plus" color="blue"wire:click="openCreateModal">
            Tambah Jenis Pemeriksaan
        </flux:button>
    </div>

    <flux:card>
        <div class="flex items-end gap-2 mb-4">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>Cari</flux:label>
                <flux:input wire:model.live.debounce.400ms="search" placeholder="Nama atau kode..." icon="magnifying-glass" />
            </flux:field>
            <flux:field>
                <flux:button variant="ghost" icon="x-mark" wire:click="resetFilter">Batal</flux:button>
            </flux:field>
        </div>

        <div class="flex items-center gap-2 mb-3 text-sm">
            <span>Tampilkan</span>
            <flux:select wire:model.live="perPage" size="sm" class="w-20">
                <flux:select.option value="10">10</flux:select.option>
                <flux:select.option value="30">30</flux:select.option>
                <flux:select.option value="50">50</flux:select.option>
            </flux:select>
            <span>entri</span>
        </div>

        <flux:table container:class="max-h-96" :paginate="$examinationTypes">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Kode</flux:table.column>
                <flux:table.column>Nama Pemeriksaan</flux:table.column>
                <flux:table.column>Harga</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($examinationTypes as $i => $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $examinationTypes->firstItem() + $i }}</flux:table.cell>
                        <flux:table.cell>{{ $item->code ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->name }}</flux:table.cell>
                        <flux:table.cell>Rp {{ number_format($item->price, 0, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="sm" variant="ghost" wire:click="openEditModal({{ $item->id }})">
                                    <x-heroicon-o-pencil class="size-4" />
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="confirmDelete({{ $item->id }})">
                                    <x-heroicon-o-trash class="size-4" />
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400">
                            Tidak ada data jenis pemeriksaan.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="examination-type-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Jenis Pemeriksaan' : 'Tambah Jenis Pemeriksaan' }}</flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Perbarui data di bawah ini.' : 'Isi data jenis pemeriksaan baru.' }}
                </flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nama Pemeriksaan</flux:label>
                <flux:input wire:model="name" placeholder="misal: Thorax PA" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Kode <span class="text-zinc-400 font-normal">(opsional)</span></flux:label>
                <flux:input wire:model="code" placeholder="misal: THX-PA" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Harga (Rp)</flux:label>
                <flux:input type="number" wire:model="price" placeholder="150000" />
                <flux:error name="price" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" color="blue">
                    {{ $editingId ? 'Simpan Perubahan' : 'Tambah' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-examination-type-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus Jenis Pemeriksaan?</flux:heading>
            <flux:text>Tindakan ini tidak dapat dibatalkan.</flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>

</div>