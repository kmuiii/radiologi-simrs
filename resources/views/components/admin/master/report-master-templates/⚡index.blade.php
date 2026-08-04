<?php

use App\Models\ReportMasterTemplate;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public int $perPage = 10;

    public ?int $editingId = null;
    public string $code = '';
    public string $templateName = '';
    public string $uraian = '';
    public bool $isActive = true;

    public ?int $deletingId = null;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'code', 'templateName', 'uraian']);
        $this->isActive = true;
        Flux::modal('master-template-form')->show();
    }

    public function openEditModal(int $id): void
    {
        $item = ReportMasterTemplate::findOrFail($id);

        $this->editingId = $item->id;
        $this->code = $item->code;
        $this->templateName = $item->template_name;
        $this->uraian = $item->uraian;
        $this->isActive = $item->is_active;

        Flux::modal('master-template-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'templateName' => 'required|string|max:255',
            'uraian' => 'required|string',
            'isActive' => 'boolean',
        ]);

        try {
            if ($this->editingId) {
                $item = ReportMasterTemplate::findOrFail($this->editingId);
                $item->update([
                    'template_name' => $validated['templateName'],
                    'uraian' => $validated['uraian'],
                    'is_active' => $validated['isActive'],
                ]);

                Flux::toast(heading: 'Berhasil', text: "Template \"{$item->template_name}\" berhasil diperbarui.", variant: 'success');
            } else {
                $item = ReportMasterTemplate::create([
                    'template_name' => $validated['templateName'],
                    'uraian' => $validated['uraian'],
                    'is_active' => $validated['isActive'],
                ]);

                Flux::toast(heading: 'Berhasil', text: "Template \"{$item->template_name}\" berhasil ditambahkan.", variant: 'success');
            }

            Flux::modal('master-template-form')->close();
            $this->reset(['editingId', 'code', 'templateName', 'uraian']);
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data.', variant: 'danger');
        }
    }

    public function toggleActive(int $id): void
    {
        try {
            $item = ReportMasterTemplate::findOrFail($id);
            $item->update(['is_active' => ! $item->is_active]);

            Flux::toast(
                heading: 'Berhasil',
                text: "Status \"{$item->template_name}\" berhasil diubah menjadi " . ($item->is_active ? 'Aktif' : 'Tidak Aktif') . '.',
                variant: 'success',
            );
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat mengubah status.', variant: 'danger');
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-master-template-confirm')->show();
    }

    public function delete(): void
    {
        try {
            $item = ReportMasterTemplate::findOrFail($this->deletingId);
            $name = $item->template_name;
            $item->delete();

            Flux::toast(heading: 'Berhasil', text: "\"{$name}\" berhasil dihapus.", variant: 'success');
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menghapus data.', variant: 'danger');
        }

        Flux::modal('delete-master-template-confirm')->close();
        $this->deletingId = null;
    }

    public function with(): array
    {
        return [
            'templates' => ReportMasterTemplate::query()
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('template_name', 'ilike', "%{$this->search}%")
                            ->orWhere('code', 'ilike', "%{$this->search}%")
                            ->orWhere('uraian', 'ilike', "%{$this->search}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ];
    }
} ?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Master Template</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal" color="blue">
            Tambah Baru
        </flux:button>
    </div>

    <flux:card>
        <div class="flex flex-wrap items-end gap-4 mb-4">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>Cari</flux:label>
                <flux:input wire:model.live.debounce.400ms="search" placeholder="Nama atau kode..." icon="magnifying-glass" />
            </flux:field>
        </div>

        <div class="flex items-center gap-2 text-sm">
            <span>Tampilkan</span>
                <flux:select wire:model.live="perPage" size="sm" class="w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="30">30</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                </flux:select>
            <span>entri</span>
        </div>

        <flux:table container:class="max-h-[32rem]" :paginate="$templates">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
                <flux:table.column>Kode</flux:table.column>
                <flux:table.column>Nama Template</flux:table.column>
                <flux:table.column>Uraian</flux:table.column>
                <flux:table.column>Aktif?</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($templates as $i => $item)
                    <flux:table.row :key="$item->id">
                        <flux:table.cell>{{ $templates->firstItem() + $i }}</flux:table.cell>
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
                        <flux:table.cell>{{ $item->code }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $item->template_name }}</flux:table.cell>
                        <flux:table.cell class="whitespace-normal break-words">
                            <span class="line-clamp-2 text-sm text-zinc-500">{{ $item->uraian }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:accent color="blue">
                                <flux:switch :checked="$item->is_active" wire:click="toggleActive({{ $item->id }})" />
                            </flux:accent>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-400">
                            Tidak ada data template.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="master-template-form" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Ubah' : 'Tambah Baru' }}</flux:heading>

            <flux:field>
                <flux:label>Kode</flux:label>
                <flux:input value="{{ $code ?: 'Akan digenerate otomatis' }}" disabled />
            </flux:field>

            <flux:field>
                <flux:label>Nama Template</flux:label>
                <flux:input wire:model="templateName" placeholder="misal: THORAX NORMAL" />
                <flux:error name="templateName" />
            </flux:field>

            <flux:field>
                <flux:label>Uraian</flux:label>
                <flux:textarea wire:model="uraian" rows="8" placeholder="Isi teks laporan lengkap..." />
                <flux:error name="uraian" />
            </flux:field>

            <flux:accent color="blue">
                <flux:field variant="inline">
                    <flux:label>Aktif?</flux:label>
                    <flux:switch wire:model="isActive"/>
                </flux:field>
            </flux:accent>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="check" color="blue">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-master-template-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus Template?</flux:heading>
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