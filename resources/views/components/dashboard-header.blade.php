@props([
    'subheading' => 'Berikut ringkasan aktivitas radiologi hari ini.',
])

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <flux:heading size="xl">
            {{ match(true) {
                now()->hour < 11 => 'Selamat Pagi',
                now()->hour < 15 => 'Selamat Siang',
                now()->hour < 19 => 'Selamat Sore',
                default          => 'Selamat Malam',
            } }}, {{ auth()->user()->name }}
        </flux:heading>
        <flux:subheading>{{ $subheading }}</flux:subheading>
    </div>

    <div class="flex flex-wrap items-center gap-3.5">
        {{-- Theme Switcher (Light / Dark Toggle Button) --}}
        <div x-data class="flex items-center">
            <flux:button
                variant="subtle"
                size="sm"
                icon-only
                x-on:click="$flux.appearance = ($flux.appearance === 'dark' || ($flux.appearance === 'system' && $flux.dark)) ? 'light' : 'dark'"
                x-tooltip.raw="Toggle theme"
                class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700/80 shadow-xs h-[38px] w-[38px] flex items-center justify-center p-0"
            >
                <x-heroicon-o-sun class="size-4.5 text-zinc-600 dark:hidden" />
                <x-heroicon-o-moon class="size-4.5 text-zinc-300 hidden dark:block" />
            </flux:button>
        </div>

        {{-- Widget Tanggal & Jam Real-Time --}}
        <div 
            x-data="{
                dateStr: '',
                timeStr: '',
                updateTime() {
                    const now = new Date();
                    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const months = [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    this.dateStr = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
                    
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    this.timeStr = `${hours}:${minutes}:${seconds} WIB`;
                }
            }"
            x-init="updateTime(); setInterval(() => updateTime(), 1000)"
            class="flex items-center gap-3.5 px-4 py-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700/80 rounded-xl shadow-xs text-sm"
        >
            <div class="flex items-center gap-2 pr-3.5 border-e border-zinc-200 dark:border-zinc-700">
                <x-heroicon-o-calendar class="size-4.5 text-zinc-400 dark:text-zinc-500" />
                <span x-text="dateStr" class="font-medium text-zinc-700 dark:text-zinc-200 whitespace-nowrap"></span>
            </div>
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="size-4.5 text-zinc-400 dark:text-zinc-500" />
                <span x-text="timeStr" class="font-mono font-semibold text-zinc-800 dark:text-zinc-100 whitespace-nowrap"></span>
            </div>
        </div>
    </div>
</div>
