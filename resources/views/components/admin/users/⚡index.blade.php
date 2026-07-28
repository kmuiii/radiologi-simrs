<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Filter
    public string $search = '';
    public string $roleFilter = '';
    public int $perPage = 10;

    // Form State
    public ?int $editingId = null;
    public string $name = '';
    public string $username = '';
    public string $password = '';
    public string $role = 'radiologist';

    // Delete State
    public ?int $deletingId = null;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'roleFilter', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'name', 'username', 'password', 'role']);
        $this->role = 'radiologist';
        Flux::modal('user-form')->show();
    }

    public function openEditModal(int $id): void
    {
        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->password = '';
        $this->role = $user->role;

        Flux::modal('user-form')->show();
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username' . ($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|in:admin,dokter,radiologist',
            'password' => $this->editingId ? 'nullable|min:8' : 'required|min:8',
        ];

        $validated = $this->validate($rules);

        try {
            if ($this->editingId) {
                $user = User::findOrFail($this->editingId);
                $user->update([
                    'name' => $validated['name'],
                    'username' => $validated['username'],
                    'role' => $validated['role'],
                    ...(filled($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
                ]);

                Flux::toast(heading: 'Berhasil', text: "Data user \"{$user->name}\" berhasil diperbarui.", variant: 'success');
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'username' => $validated['username'],
                    'role' => $validated['role'],
                    'password' => Hash::make($validated['password']),
                ]);

                Flux::toast(heading: 'Berhasil', text: "User \"{$user->name}\" berhasil ditambahkan.", variant: 'success');
            }

            Flux::modal('user-form')->close();
            $this->reset(['editingId', 'name', 'username', 'password', 'role']);
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menyimpan data user.', variant: 'danger');
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('delete-user-confirm')->show();
    }

    public function delete(): void
    {
        try {
            $user = User::findOrFail($this->deletingId);

            if ($user->id === auth()->id()) {
                Flux::toast(heading: 'Gagal', text: 'Anda tidak dapat menghapus akun Anda sendiri.', variant: 'danger');
                Flux::modal('delete-user-confirm')->close();
                return;
            }

            $name = $user->name;
            $user->delete();

            Flux::toast(heading: 'Berhasil', text: "User \"{$name}\" berhasil dihapus.", variant: 'success');
        } catch (\Throwable $e) {
            Flux::toast(heading: 'Gagal', text: 'Terjadi kesalahan saat menghapus data user.', variant: 'danger');
        }

        Flux::modal('delete-user-confirm')->close();
        $this->deletingId = null;
    }

    public function with(): array
    {
        return [
            'users' => User::query()
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'ilike', "%{$this->search}%")
                            ->orWhere('username', 'ilike', "%{$this->search}%");
                    });
                })
                ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
                ->latest()
                ->paginate($this->perPage),
        ];
    }
} ?>

<div class="space-y-6">

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Manajemen Users</flux:heading>
        <flux:button variant="primary" icon="plus" color="blue" wire:click="openCreateModal">
            Tambah User
        </flux:button>
    </div>

    <flux:card>
        <div class="flex flex-wrap items-end gap-4 mb-4">
            <flux:field class="flex-1 max-w-sm">
                <flux:label>Cari</flux:label>
                <flux:input wire:model.live.debounce.400ms="search" placeholder="Nama atau username..." icon="magnifying-glass" />
            </flux:field>

            <flux:field class="w-48">
                <flux:label>Role</flux:label>
                <flux:select wire:model.live="roleFilter">
                    <flux:select.option value="">- Semua Role -</flux:select.option>
                    <flux:select.option value="admin">Admin</flux:select.option>
                    <flux:select.option value="radiologist">Dokter Radiologi</flux:select.option>
                    <flux:select.option value="dokter">Dokter</flux:select.option>
                </flux:select>
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

        <flux:table container:class="max-h-96" :paginate="$users">
            <flux:table.columns sticky class="bg-white dark:bg-zinc-900">
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Nama</flux:table.column>
                <flux:table.column>Username</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($users as $i => $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>{{ $users->firstItem() + $i }}</flux:table.cell>
                        <flux:table.cell>{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->username }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="pill" :color="$user->role_color">
                                   {{ $user->role_label }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="sm" variant="ghost" wire:click="openEditModal({{ $user->id }})">
                                    <x-heroicon-o-pencil class="size-4" />
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="confirmDelete({{ $user->id }})">
                                    <x-heroicon-o-trash class="size-4" />
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-400">
                            Tidak ada data user.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal name="user-form" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit User' : 'Tambah User' }}</flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Perbarui data user di bawah ini.' : 'Isi data untuk membuat user baru.' }}
                </flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nama</flux:label>
                <flux:input wire:model="name" placeholder="Nama lengkap" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Username</flux:label>
                <flux:input type="username" wire:model="username" placeholder="username" />
                <flux:error name="username" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="role">
                    <flux:select.option value="radiologist">Dokter Radiologi</flux:select.option>
                    <flux:select.option value="dokter">Dokter</flux:select.option>
                    <flux:select.option value="admin">Admin</flux:select.option>
                </flux:select>
                <flux:error name="role" />
            </flux:field>

            <flux:field>
                <flux:label>
                    Password
                    @if($editingId)
                        <span class="text-zinc-400 font-normal"> (kosongkan jika tidak diubah)</span>
                    @endif
                </flux:label>
                <flux:input type="password" wire:model="password" placeholder="••••••••" />
                <flux:error name="password" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" color="blue">
                    {{ $editingId ? 'Simpan Perubahan' : 'Tambah User' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-user-confirm" class="md:w-80">
        <div class="space-y-4">
            <flux:heading size="lg">Hapus User?</flux:heading>
            <flux:text>Tindakan ini tidak dapat dibatalkan. User yang dihapus akan kehilangan akses ke sistem.</flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">Ya, Hapus</flux:button>
            </div>
        </div>
    </flux:modal>

</div>