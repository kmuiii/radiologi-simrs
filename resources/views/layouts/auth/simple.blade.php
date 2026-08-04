@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="auth-bg antialiased relative overflow-x-hidden">

        {{-- Decorative blobs — respond to dark mode via CSS --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" aria-hidden="true">
            <div class="blob-blue"></div>
            <div class="blob-orange"></div>
        </div>

        {{-- Floating theme switcher — top-right corner --}}
        <div class="fixed top-4 right-4 z-50" x-data>
            <flux:button
                variant="subtle"
                size="sm"
                icon-only
                x-on:click="$flux.appearance = ($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'light' : 'dark'"
                x-tooltip.raw="Toggle theme"
                class="rounded-lg bg-white/80 dark:bg-zinc-800/80 backdrop-blur border border-zinc-200 dark:border-zinc-700 shadow-sm"
            >
                <x-heroicon-o-sun   class="size-4 text-zinc-600 dark:hidden" />
                <x-heroicon-o-moon  class="size-4 text-zinc-300 hidden dark:block" />
            </flux:button>
        </div>

        {{-- Centered content --}}
        <div class="relative z-10 flex min-h-svh flex-col items-center justify-center p-6 md:p-10">
            <div class="w-full max-w-sm md:max-w-[460px]">
                <div class="glass-card px-8 py-10 md:px-10 md:py-12">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
