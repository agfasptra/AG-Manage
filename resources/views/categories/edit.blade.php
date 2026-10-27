<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('categories.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Edit Kategori</h1>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui kategori "{{ $category->name }}"</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Jenis Kategori --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Jenis Kategori <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="income" class="sr-only peer"
                                       {{ old('type', $category->type) === 'income' ? 'checked' : '' }}>
                                <div class="flex items-center justify-center gap-2 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-slate-300 transition-all duration-200">
                                    <i data-lucide="arrow-down-left" class="w-5 h-5 text-green-600"></i>
                                    <span class="text-sm font-medium text-slate-700">Pemasukan</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="type" value="expense" class="sr-only peer"
                                       {{ old('type', $category->type) === 'expense' ? 'checked' : '' }}>
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
                        <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}"
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
                            <input type="color" name="color" id="color" value="{{ old('color', $category->color ?? '#059669') }}"
                                   class="w-12 h-12 rounded-xl border border-slate-300 cursor-pointer p-1">
                            <span class="text-xs text-slate-500">Pilih warna untuk membedakan kategori di grafik.</span>
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
                            <i data-lucide="save" class="w-4 h-4"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

            {{-- Danger Zone: Delete Category --}}
            @php $hasTransactions = $category->transactions()->exists(); @endphp
            <div class="bg-white border border-red-200 rounded-2xl p-5 shadow-xs" x-data="{ confirmDelete: false }">
                <h2 class="text-sm font-semibold text-red-600 uppercase tracking-wider mb-3">Zona Berbahaya</h2>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-700">Hapus kategori ini secara permanen.</p>
                        @if($hasTransactions)
                            <p class="text-xs text-amber-600 mt-1 flex items-center gap-1">
                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                                Tidak bisa dihapus karena masih ada transaksi terkait. Gunakan nonaktifkan sebagai gantinya.
                            </p>
                        @else
                            <p class="text-xs text-slate-500">Kategori ini belum memiliki transaksi dan bisa dihapus.</p>
                        @endif
                    </div>
                    @if(!$hasTransactions)
                        <button @click="confirmDelete = true"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors shrink-0">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            Hapus
                        </button>
                    @endif
                </div>

                {{-- Delete Confirmation Modal --}}
                <div x-show="confirmDelete" x-transition
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl" @click.away="confirmDelete = false">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 text-center">Hapus Kategori?</h3>
                        <p class="text-sm text-slate-500 text-center mt-2">Kategori "{{ $category->name }}" akan dihapus secara permanen.</p>
                        <div class="flex gap-3 mt-5">
                            <button @click="confirmDelete = false"
                                    class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                Batal
                            </button>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full px-4 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition-colors">
                                    Ya, Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
