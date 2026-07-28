<?php

use Livewire\Component;
use App\Models\MasterPoli;
use Flux\Flux;
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

    // Delete State
    public ?int $deletingId = null;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'name', 'code']);
        Flux::modal('poli-form')->show();
    }

    public function openEditModal(int $id): void
    {
        $item = MasterPoli::findOrFail($id);

        $this->editingId = $item->id;
        $this->name = $item->name;
        $this->code = $item->code ?? '';

        Flux::modal('poli-form')->show();
    }

    public function save():void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:master_poli,code' . ($this->editingId ? ",{$this->editingId}" : ''),
        ]);

        try {
            if ($this->editingId) {
                $item = MasterPoli::findOrFail($this->editingId);
                $item->update($validated);

                Flux::toast(heading: 'Berhasil', text: "Poli \"{$item->name}\" berhasil diperbarui.", variant: 'success');
            } else {
                $item = MasterPoli::create($validated);

                Flux::toast(heading: 'Berhasil', text: "Poli \"{$item->name}\" berhasil ditambahkan.", variant: 'success');
            }

            Flux::modal('poli-form')->close();
            $this->reset(['editingId', 'name', 'code']);
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data.', variant: 'danger');
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-poli-confirm')->show();
    }

    public function delete(): void
    {
        try {
            $item = MasterPoli::findOrFail($this->deletingId);

            if ($item->pelayanan()->exists()) {
                Flux::toast(
                    heading: 'Gagal',
                    text: "Tidak dapat menghapus \"{$item->name}\" karena masih digunakan pada data Pelayanan.",
                    variant: 'danger',
                );
                Flux::modal('delete-poli-confirm')->close();
                return;
            }

            $name = $item->name;
            $item->delete();

            Flux::toast(heading: 'Berhasil', text: "\"{$name}\" berhasil dihapus.", variant: 'success');
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menghapus data.', variant: 'danger');
        }

        Flux::modal('delete-poli-confirm')->close();
        $this->deletingId = null;
    }

    public function with(): array
    {
        return [
            'masterPoli' => MasterPoli::query()
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
};
?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Master Poli</flux:heading>
        <flux:button variant="primary" icon="plus" color="blue" wire:click="openCreateModal">
            Tambah Poli
        </flux:button>
    </div>

    <flux:card>
        <div class="flex flex-wrap items-end gap-4 mb-4">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>Cari</flux:label>
                <flux:input wire:model.live.debounce.400ms="search" placeholder="Nama atau kode..." icon="magnifying-glass" />
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

        <flux:table container:class="max-h-96" :paginate="$masterPoli">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Kode</flux:table.column>
                <flux:table.column>Nama Poli</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($masterPoli as $i => $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $masterPoli->firstItem() + $i }}</flux:table.cell>
                        <flux:table.cell>{{ $item->code ?? '-' }}</flux:table.cell>
                        <flux:table.cell>{{ $item->name }}</flux:table.cell>
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
                            Tidak ada data Poli.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="poli-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit Data Poli' : 'Tambah Poli' }}</flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Perbarui data di bawah ini.' : 'Isi data poli baru.' }}
                </flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nama Poli</flux:label>
                <flux:input wire:model="name" placeholder="misal: Poli Umum" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Kode <span class="text-zinc-400 font-normal">(opsional)</span></flux:label>
                <flux:input wire:model="code" placeholder="misal: THX-PA" />
                <flux:error name="code" />
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

    <flux:modal name="delete-poli-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus Data Poli?</flux:heading>
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