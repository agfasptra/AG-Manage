<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Daftar Akun Baru</h1>
        <p class="text-sm text-slate-500 mt-1">Mulai pencatatan keuangan pribadi Anda bersama AG Manage.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nama Lengkap')" class="font-medium text-slate-700" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Misal: Budi Santoso" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Alamat Email')" class="font-medium text-slate-700" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Kata Sandi')" class="font-medium text-slate-700" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Ulangi Kata Sandi')" class="font-medium text-slate-700" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password"
                            placeholder="Ulangi kata sandi" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full py-3 text-sm">
                {{ __('Daftar Sekarang') }}
            </x-primary-button>
        </div>

        <div class="text-center pt-3 border-t border-slate-100">
            <span class="text-xs text-slate-500">Sudah memiliki akun?</span>
            <a href="{{ route('login') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline ms-1">
                Masuk di sini
            </a>
        </div>
    </form>
</x-guest-layout>
