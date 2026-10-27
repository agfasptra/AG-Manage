<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('categories.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Tambah Kategori Baru</h1>
                <p class="text-xs text-slate-500 mt-0.5">Buat kategori kustom untuk mencatat transaksi Anda</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <form method="POST" action="{{ route('categories.store') }}" class="space-y-6">
                    @csrf

                    {{-- Jenis Kategori --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Jenis Kategori <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="income" class="sr-only peer"
                                       {{ old('type', 'income') === 'income' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-slate-300 transition-all duration-200">
                                    <i data-lucide="arrow-down-left" class="w-5 h-5 text-green-600"></i>
                                    <span class="text-sm font-medium text-slate-700">Pemasukan</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="expense" class="sr-only peer"
                                       {{ old('type') === 'expense' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-red-500 peer-checked:bg-red-50 hover:border-slate-300 transition-all duration-200">
                                    <i data-lucide="arrow-up-right" class="w-5 h-5 text-red-600"></i>
                                    <span class="text-sm font-medium text-slate-700">Pengeluaran</span>
                                </div>
                            </label>
                        </div>
                        @error('type')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Nama Kategori --}}
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Nama Kategori <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}"
                               placeholder="Contoh: Bonus, Jajan, Parkir"
                               class="w-full rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm px-4 py-3 transition-colors"
                               required maxlength="100" autofocus>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Warna --}}
                    <div>
                        <label for="color" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Warna <span class="text-slate-400 font-normal">(opsional)</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="color" id="color" value="{{ old('color', '#059669') }}"
                                   class="w-12 h-12 rounded-xl border border-slate-300 cursor-pointer p-1">
                            <span class="text-xs text-slate-500">Pilih warna untuk membedakan kategori di grafik dan laporan.</span>
                        </div>
                        @error('color')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('categories.index') }}"
                           class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all duration-200 shadow-sm hover:shadow-md">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Buat Kategori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
