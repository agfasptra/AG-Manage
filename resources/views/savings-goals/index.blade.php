<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Target Tabungan</h1>
                <p class="text-xs text-slate-500 mt-0.5">Rencanakan impian dan pantau alokasi tabungan masa depan Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('savings-goals.create') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition shadow-sm hover:shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Buat Target Tabungan</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
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

            {{-- ═══════════════════════════════════════ --}}
            {{-- SUMMARY METRICS CARDS                   --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Target --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Target</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i data-lucide="flag" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">
                        Rp{{ number_format($totalTarget, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Akumulasi seluruh target impian</p>
                </div>

                {{-- Total Saved --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Terkumpul</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="piggy-bank" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-emerald-600 mt-2 tracking-tight">
                        Rp{{ number_format($totalSaved, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">{{ $overallPercentage }}% dari total target terkumpul</p>
                </div>

                {{-- Remaining --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Sisa Target</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="compass" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-indigo-600 mt-2 tracking-tight">
                        Rp{{ number_format($totalRemaining, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Kekurangan dana menuju impian</p>
                </div>

                {{-- Active Goals Count --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Target Aktif</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i data-lucide="sparkles" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-900 mt-2 tracking-tight">
                        {{ $activeGoalsCount }}
                    </p>
                    <p class="text-xs text-slate-400 mt-1">Target tabungan yang sedang berjalan</p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- STATUS FILTER TABS                      --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="flex items-center gap-1.5 border-b border-slate-200 pb-3">
                <a href="{{ route('savings-goals.index', ['status' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'all' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Semua
                </a>
                <a href="{{ route('savings-goals.index', ['status' => 'active']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'active' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Aktif
                </a>
                <a href="{{ route('savings-goals.index', ['status' => 'paused']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'paused' ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Dijeda
                </a>
                <a href="{{ route('savings-goals.index', ['status' => 'completed']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $status === 'completed' ? 'bg-purple-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    Selesai
                </a>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- SAVINGS GOALS GRID                      --}}
            {{-- ═══════════════════════════════════════ --}}
            @if ($goals->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($goals as $goal)
                        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                            <div>
                                {{-- Card Header: Title & Status --}}
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-slate-900 text-base truncate">{{ $goal->name }}</h3>
                                        @if ($goal->target_date)
                                            <p class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                                Target: {{ $goal->target_date->translatedFormat('d F Y') }}
                                            </p>
                                        @endif
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold shrink-0
                                        @if ($goal->status === 'active') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif ($goal->status === 'paused') bg-amber-50 text-amber-700 border border-amber-200
                                        @else bg-purple-50 text-purple-700 border border-purple-200
                                        @endif">
                                        {{ $goal->status_label }}
                                    </span>
                                </div>

                                {{-- Progress bar --}}
                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-bold text-slate-900">{{ $goal->formatted_saved }}</span>
                                        <span class="text-slate-400">dari {{ $goal->formatted_target_amount }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                        <div class="h-2.5 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 transition-all duration-500"
                                            style="width: {{ $goal->progress_percentage }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                                        <span class="font-semibold text-purple-600">{{ $goal->progress_percentage }}%</span>
                                        <span>Sisa: {{ $goal->formatted_remaining }}</span>
                                    </div>
                                </div>

                                @if ($goal->notes)
                                    <p class="text-xs text-slate-500 mt-3 line-clamp-2 italic">
                                        "{{ $goal->notes }}"
                                    </p>
                                @endif
                            </div>

                            {{-- Footer action link --}}
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <span class="text-slate-400">{{ $goal->contributions->count() }} alokasi tercatat</span>
                                <a href="{{ route('savings-goals.show', $goal) }}"
                                   class="font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 transition">
                                    Lihat Detail & Alokasi
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white border border-slate-200/90 rounded-2xl p-12 text-center shadow-xs">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="piggy-bank" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Belum Ada Target Tabungan</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
                        Tentukan impian finansial Anda seperti dana darurat, liburan, atau gadget baru dan lacak progresnya setiap saat.
                    </p>
                    <div class="mt-4">
                        <a href="{{ route('savings-goals.create') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition shadow-xs">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Mulai Target Pertama</span>
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
