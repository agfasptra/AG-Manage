<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('wallets.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Tambah Dompet Baru</h1>
                <p class="text-xs text-slate-500 mt-0.5">Buat dompet baru untuk mencatat keuangan Anda</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <form method="POST" action="{{ route('wallets.store') }}" class="space-y-6">
                    @csrf

                    {{-- Nama Dompet --}}
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Nama Dompet <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               placeholder="Contoh: BCA, Tunai, GoPay"
                               class="w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm px-4 py-3 transition-colors"
                               required maxlength="100" autofocus>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Jenis Dompet --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Jenis Dompet <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @php
                                $types = [
                                    'cash' => ['label' => 'Tunai', 'icon' => 'banknote'],
                                    'bank' => ['label' => 'Bank', 'icon' => 'landmark'],
                                    'ewallet' => ['label' => 'E-Wallet', 'icon' => 'smartphone'],
                                    'other' => ['label' => 'Lainnya', 'icon' => 'wallet'],
                                ];
                            @endphp
                            @foreach($types as $value => $type)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="type" value="{{ $value }}"
                                           class="sr-only peer" {{ old('type', 'cash') === $value ? 'checked' : '' }}>
                                    <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:border-slate-300 transition-all duration-200">
                                        <i data-lucide="{{ $type['icon'] }}" class="w-6 h-6 text-slate-500 peer-checked:text-emerald-600"></i>
                                        <span class="text-xs font-medium text-slate-700">{{ $type['label'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('type')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Saldo Awal --}}
                    <div>
                        <label for="opening_balance" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Saldo Awal <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-500 mb-2">Masukkan jumlah uang yang ada saat ini di dompet ini. Saldo awal tidak dihitung sebagai pemasukan.</p>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">Rp</span>
                            <input type="number" name="opening_balance" id="opening_balance"
                                   value="{{ old('opening_balance', '0') }}"
                                   class="w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm pl-11 pr-4 py-3 tabular-nums transition-colors"
                                   min="0" step="1" required>
                        </div>
                        @error('opening_balance')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label for="description" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Deskripsi <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Catatan tentang dompet ini..."
                                  class="w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm px-4 py-3 transition-colors resize-none"
                                  maxlength="500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('wallets.index') }}"
                           class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Buat Dompet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
