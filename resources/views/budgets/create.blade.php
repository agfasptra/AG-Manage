<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Buat Anggaran Baru</h1>
                <p class="text-xs text-slate-500 mt-0.5">Tentukan batas pengeluaran untuk kategori tertentu pada periode {{ $periodLabel }}</p>
            </div>
            <a href="{{ route('budgets.index', ['month' => $month, 'year' => $year]) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <form method="POST" action="{{ route('budgets.store') }}" class="space-y-5">
                    @csrf

                    {{-- Period Selection --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="period_month" value="Bulan" />
                            <select id="period_month" name="period_month" class="mt-1 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ old('period_month', $month) == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('period_month')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="period_year" value="Tahun" />
                            <select id="period_year" name="period_year" class="mt-1 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                @for ($y = Carbon\Carbon::now()->year - 1; $y <= Carbon\Carbon::now()->year + 3; $y++)
                                    <option value="{{ $y }}" {{ old('period_year', $year) == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('period_year')" class="mt-1" />
                        </div>
                    </div>

                    {{-- Category Selection --}}
                    <div>
                        <x-input-label for="category_id" value="Kategori Pengeluaran" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full text-sm border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500" required>
                            <option value="">-- Pilih Kategori Pengeluaran --</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                    </div>

                    {{-- Amount --}}
                    <div>
                        <x-input-label for="amount" value="Batas Nominal Anggaran (Rp)" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
                                Rp
                            </span>
                            <x-text-input id="amount" name="amount" type="number" step="1000" min="1000" class="pl-11 block w-full text-sm" :value="old('amount')" placeholder="Contoh: 1500000" required />
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Batas maksimal pengeluaran yang direncanakan untuk kategori ini dalam 1 bulan.</p>
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('budgets.index', ['month' => $month, 'year' => $year]) }}"
                           class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                            Batal
                        </a>
                        <x-primary-button class="text-xs">
                            Simpan Anggaran
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
