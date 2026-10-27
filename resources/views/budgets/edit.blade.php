<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Ubah Anggaran</h1>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui batas anggaran untuk {{ $budget->category?->name }} ({{ $periodLabel }})</p>
            </div>
            <a href="{{ route('budgets.index', ['month' => $budget->period_month, 'year' => $budget->period_year]) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-xs">
                <form method="POST" action="{{ route('budgets.update', $budget) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Category & Period info (Read-only) --}}
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="w-4 h-4 rounded-full" style="background-color: {{ $budget->category?->color ?? '#94a3b8' }}"></span>
                            <div>
                                <span class="text-xs text-slate-400">Kategori & Periode</span>
                                <h3 class="font-bold text-slate-900 text-sm">{{ $budget->category?->name }}</h3>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-slate-600 bg-white px-3 py-1 rounded-full border border-slate-200">
                            {{ $periodLabel }}
                        </span>
                    </div>

                    {{-- Amount --}}
                    <div>
                        <x-input-label for="amount" value="Batas Nominal Anggaran (Rp)" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm font-semibold">
                                Rp
                            </span>
                            <x-text-input id="amount" name="amount" type="number" step="1000" min="1000" class="pl-11 block w-full text-sm" :value="old('amount', (int)$budget->amount)" required />
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Pengeluaran tercatat saat ini: <strong>{{ $budget->formatted_spent }}</strong></p>
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ route('budgets.index', ['month' => $budget->period_month, 'year' => $budget->period_year]) }}"
                           class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-200 transition">
                            Batal
                        </a>
                        <x-primary-button class="text-xs">
                            Simpan Perubahan
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
