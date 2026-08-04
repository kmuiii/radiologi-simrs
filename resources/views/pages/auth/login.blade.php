<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-7">

        {{-- ── Brand Header ── --}}
        <div class="flex flex-col items-center gap-3 text-center">
            <a href="{{ route('home') }}" wire:navigate class="group">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-105">
                    <x-app-logo-icon class="size-9 fill-current text-white" />
                </span>
            </a>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-gray-800">RS Techno Medic</h1>
                <p class="mt-0.5 text-sm font-medium tracking-wide text-sky-600/80">Radiology Information System</p>
            </div>
        </div>

        {{-- ── Divider ── --}}
        <div class="h-px w-full bg-gradient-to-r from-transparent via-gray-200 to-transparent"></div>

        {{-- ── Session Status ── --}}
        @if (session('status'))
            <div class="rounded-xl bg-blue-50 px-4 py-3 text-center text-sm text-blue-700 ring-1 ring-blue-200">
                {{ session('status') }}
            </div>
        @endif

        {{-- ── Login Form ── --}}
        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- Username --}}
            <div class="flex flex-col gap-1.5">
                <flux:input
                    name="username"
                    label="{{ __('Username') }}"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="{{ __('Masukkan username Anda') }}"
                    class="w-full"
                />
            </div>

            {{-- Password --}}
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <flux:label>{{ __('Password') }}</flux:label>
                    @if (Route::has('password.request'))
                        <flux:link
                            :href="route('password.request')"
                            wire:navigate
                            class="text-xs font-medium text-sky-600 hover:text-sky-700 transition-colors"
                        >
                            {{ __('Lupa password?') }}
                        </flux:link>
                    @endif
                </div>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Masukkan password Anda') }}"
                    viewable
                    class="w-full"
                />
            </div>

            {{-- Remember Me --}}
            <flux:accent color="blue">
                <flux:checkbox
                    name="remember"
                    :label="__('Ingat saya di perangkat ini')"
                    :checked="old('remember')"
                />
            </flux:accent>

            {{-- Submit --}}
            <div class="pt-1">
                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full"
                    data-test="login-button"
                    color="blue"
                >
                    {{ __('Masuk') }}
                </flux:button>
            </div>
        </form>

        {{-- ── Footer Note ── --}}
        <p class="text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} RS Techno Medic &mdash; All rights reserved
        </p>

    </div>
</x-layouts::auth>
