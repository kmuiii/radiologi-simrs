@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="app-bg min-h-screen antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            
            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Radiologi')" class="grid">
                    <flux:navlist.item icon="home" href="{{ route('admin.dashboard') }}" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        Dashboard
                    </flux:navlist.item>
                    <flux:navlist.item icon="users" href="{{ route('admin.users.index') }}" :current="request()->routeIs('admin.users.*')" wire:navigate>
                        Users
                    </flux:navlist.item>

                    {{-- Master Data --}}
                    <flux:sidebar.group icon="folder" expandable heading="Master Data" class="grid">
                        <flux:navlist.item href="{{ route('admin.master.examination-types.index') }}" :current="request()->routeIs('admin.master.examination-types.*')" wire:navigate>
                            Jenis Pemeriksaan
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.master.master-poli.index') }}" :current="request()->routeIs('admin.master.master-poli.*')" wire:navigate>
                            Master Poli
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.master.report-master-templates.index') }}" :current="request()->routeIs('admin.master.report-master-templates.*')" wire:navigate>
                            Master Template
                        </flux:navlist.item>
                    </flux:sidebar.group>

                    {{-- Pendaftaran --}}
                    <flux:sidebar.group icon="inbox-stack" expandable heading="Pendaftaran" class="grid">
                        <flux:navlist.item href="{{ route('admin.pendaftaran.registrasi.index') }}" :current="request()->routeIs('admin.pendaftaran.registrasi.*')" wire:navigate>
                            Registrasi
                        </flux:navlist.item>
                        <flux:navlist.item href="{{ route('admin.pendaftaran.tindakan.index') }}" :current="request()->routeIs('admin.pendaftaran.tindakan.*')" wire:navigate>
                            Pelayanan
                        </flux:navlist.item>
                    </flux:sidebar.group>

                    <flux:navlist.item icon="wrench-screwdriver" href="{{ route('admin.configuration.index') }}" :current="request()->routeIs('admin.configuration.*')" wire:navigate>
                        Configuration
                    </flux:navlist.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <!-- <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav> -->

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()?->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()?->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()?->name"
                                    :initials="auth()->user()?->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()?->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()?->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
