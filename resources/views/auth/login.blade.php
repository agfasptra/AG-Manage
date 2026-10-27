<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Masuk ke Akun</h1>
        <p class="text-sm text-slate-500 mt-1">Masukkan kredensial Anda untuk mengelola keuangan.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Alamat Email')" class="font-medium text-slate-700" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Kata Sandi')" class="font-medium text-slate-700" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-emerald-600 hover:text-emerald-700 hover:underline" href="{{ route('password.request') }}">
                        {{ __('Lupa kata sandi?') }}
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded-md border-slate-300 text-emerald-600 shadow-xs focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-xs text-slate-600">{{ __('Ingat saya') }}</span>
            </label>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full py-3 text-sm">
                {{ __('Masuk ke Akun') }}
            </x-primary-button>
        </div>

        <div class="text-center pt-3 border-t border-slate-100">
            <span class="text-xs text-slate-500">Belum memiliki akun?</span>
            <a href="{{ route('register') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline ms-1">
                Daftar sekarang
            </a>
        </div>
    </form>
</x-guest-layout>
