<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => ($code ?? 'Error') . ' - ' . ($title ?? 'Terjadi Kesalahan')])
    </head>
    <body class="app-bg antialiased min-h-screen flex flex-col items-center justify-center p-6 select-none overflow-x-hidden">

        <div class="relative z-10 max-w-lg w-full text-center space-y-6">
            {{-- Large HTTP Status Code --}}
            <div class="font-extrabold text-7xl sm:text-8xl md:text-9xl tracking-tight text-blue-600 dark:text-blue-400 drop-shadow-xs">
                {{ $code ?? '404' }}
            </div>

            {{-- Title & Description --}}
            <div class="space-y-3">
                <h1 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100 tracking-tight leading-snug">
                    {{ $title ?? 'Terjadi Kesalahan' }}
                </h1>

                @if(!empty($description))
                    <p class="text-sm sm:text-base text-zinc-500 dark:text-zinc-400 max-w-md mx-auto leading-relaxed">
                        {{ $description }}
                    </p>
                @endif
            </div>

            {{-- Primary Action Button --}}
            <div class="pt-4 flex items-center justify-center">
                <flux:button
                    variant="primary"
                    color="blue"
                    icon="arrow-left"
                    onclick="if (window.history.length > 1 && document.referrer) { window.history.back(); } else { window.location.href = '{{ auth()->check() ? route('dashboard') : route('login') }}'; }"
                    class="cursor-pointer font-medium px-6 py-2.5"
                >
                    Kembali
                </flux:button>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
