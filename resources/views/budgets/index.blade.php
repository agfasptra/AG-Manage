<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Anggaran Bulanan</h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola batas pengeluaran per kategori untuk mengendalikan keuangan Anda</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                {{-- Month & Year Selector Form --}}
                <form method="GET" action="{{ route('budgets.index') }}" class="flex items-center gap-1.5" x-data>
                    <select name="month" @change="$el.closest('form').submit()"
                        class="text-xs border-slate-300 rounded-lg shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-2.5">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <select name="year" @change="$el.closest('form').submit()"
                        class="text-xs border-slate-300 rounded-lg shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-2.5">
                        @for ($y = Carbon\Carbon::now()->year - 2; $y <= Carbon\Carbon::now()->year + 2; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </form>

                <a href="{{ route('budgets.create', ['month' => $month, 'year' => $year]) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition shadow-sm hover:shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Buat Anggaran</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ deleteModalOpen: false, deleteUrl: '', deleteName: '' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-emerald-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            @if (session('warning'))
                <div class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0"></i>
                    <span>{{ session('warning') }}</span>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-amber-400 hover:text-amber-600">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            {{-- ═══════════════════════════════════════ --}}
            {{-- SUMMARY CARDS & SAFE-TO-SPEND           --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Budget --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Anggaran</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="target" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">
                        Rp{{ number_format($totalBudget, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $budgets->count() }} kategori dianggarkan ({{ $periodLabel }})</p>
                </div>

                {{-- Spent --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Terpakai</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <i data-lucide="trending-down" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-rose-600 mt-2 tracking-tight">
                        Rp{{ number_format($totalSpent, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $overallPercentage }}% dari total anggaran</p>
                </div>

                {{-- Remaining --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sisa Kuota Anggaran</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="piggy-bank" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-600 mt-2 tracking-tight">
                        Rp{{ number_format($totalRemaining, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Kuota belanja yang belum terpakai</p>
                </div>

                {{-- Safe-to-Spend Estimate (PRD 5.9) --}}
                <div class="bg-gradient-to-br from-emerald-900 to-teal-950 text-white rounded-2xl p-5 shadow-sm relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-emerald-200 uppercase tracking-wider">Aman Dibelanjakan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                                Estimasi PRD 5.9
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-emerald-100 mt-2 tracking-tight">
                            {{ $safeToSpend['formatted_safe_to_spend'] }}
                        </p>
                        <p class="text-[11px] text-emerald-200/80 mt-1 line-clamp-1" title="{{ $safeToSpend['explanation'] }}">
                            Saldo Dompet - Sisa Komitmen Anggaran
                        </p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl pointer-events-none"></div>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- RULE-BASED INSIGHTS (PRD 5.12)          --}}
            {{-- ═══════════════════════════════════════ --}}
            @if (!empty($insights))
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-3">
                        <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
                        Insight Finansial Berbasis Data
                        <span class="text-xs font-normal text-slate-400">({{ $periodLabel }})</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($insights as $insight)
                            <div class="flex items-start gap-3 p-3.5 rounded-xl border
                                @if($insight['level'] === 'danger') bg-red-50/70 border-red-200 text-red-900
                                @elseif($insight['level'] === 'warning') bg-amber-50/70 border-amber-200 text-amber-900
                                @elseif($insight['level'] === 'success') bg-emerald-50/70 border-emerald-200 text-emerald-900
                                @else bg-sky-50/70 border-sky-200 text-sky-900
                                @endif">
                                <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center mt-0.5
                                    @if($insight['level'] === 'danger') bg-red-100 text-red-600
                                    @elseif($insight['level'] === 'warning') bg-amber-100 text-amber-600
                                    @elseif($insight['level'] === 'success') bg-emerald-100 text-emerald-600
                                    @else bg-sky-100 text-sky-600
                                    @endif">
                                    <i data-lucide="{{ $insight['icon'] }}" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-bold leading-tight">{{ $insight['title'] }}</h4>
                                    <p class="text-xs mt-0.5 leading-relaxed opacity-90">{{ $insight['message'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════ --}}
            {{-- BUDGET LIST CARDS                       --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Daftar Anggaran Kategori</h2>
                        <p class="text-xs text-slate-500">Evaluasi realisasi pengeluaran dibanding batas anggaran</p>
                    </div>
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        {{ $periodLabel }}
                    </span>
                </div>

                @if ($budgets->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($budgets as $b)
                            <div class="p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition-all shadow-2xs space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-3.5 h-3.5 rounded-full shrink-0 shadow-xs" style="background-color: {{ $b->category?->color ?? '#94a3b8' }}"></span>
                                        <div>
                                            <h3 class="font-bold text-slate-900 text-sm">{{ $b->category?->name ?? 'Kategori Dihapus' }}</h3>
                                            <p class="text-xs text-slate-400">Target: {{ $b->formatted_amount }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        @if ($b->is_exceeded)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-700">
                                                <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                                Melebihi Anggaran
                                            </span>
                                        @elseif ($b->is_warning)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700">
                                                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                                Waspada ({{ $b->percentage_used }}%)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                                                <i data-lucide="check" class="w-3 h-3"></i>
                                                Aman ({{ $b->percentage_used }}%)
                                            </span>
                                        @endif

                                        <a href="{{ route('budgets.edit', $b) }}"
                                           class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition"
                                           title="Ubah Anggaran">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </a>

                                        <button @click="deleteModalOpen = true; deleteUrl = '{{ route('budgets.destroy', $b) }}'; deleteName = '{{ $b->category?->name ?? 'Anggaran' }}'"
                                                class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                                title="Hapus Anggaran">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- Progress bar --}}
                                <div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full transition-all duration-500
                                            @if($b->is_exceeded) bg-red-500
                                            @elseif($b->is_warning) bg-amber-500
                                            @else bg-emerald-500
                                            @endif"
                                            style="width: {{ min($b->percentage_used, 100) }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs mt-1.5 text-slate-500">
                                        <span>Terpakai: <strong class="text-slate-800">{{ $b->formatted_spent }}</strong></span>
                                        <span>Sisa: <strong class="{{ $b->is_exceeded ? 'text-red-600' : 'text-emerald-700' }}">{{ $b->formatted_remaining }}</strong></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="target" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Belum Ada Anggaran untuk {{ $periodLabel }}</h3>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                            Buat batas anggaran untuk kategori pengeluaran Anda agar pengeluaran tetap terkontrol dan terhindar dari defisit.
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('budgets.create', ['month' => $month, 'year' => $year]) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition shadow-xs">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Buat Anggaran Pertama</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- Delete Confirmation Modal --}}
        <div x-show="deleteModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="deleteModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                     @click="deleteModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="deleteModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-red-100 text-red-600 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-bold text-slate-900" id="modal-title">Hapus Anggaran</h3>
                            <div class="mt-2">
                                <p class="text-xs text-slate-600">
                                    Apakah Anda yakin ingin menghapus anggaran untuk kategori <strong x-text="deleteName"></strong>? Data riwayat transaksi tidak akan terhapus.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <form :action="deleteUrl" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-xs px-4 py-2 bg-red-600 text-xs font-semibold text-white hover:bg-red-700 transition sm:w-auto">
                                Ya, Hapus Anggaran
                            </button>
                        </form>
                        <button type="button"
                                @click="deleteModalOpen = false"
                                class="mt-2 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-xs px-4 py-2 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition sm:w-auto">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
