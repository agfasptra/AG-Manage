<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-bold text-xl text-slate-900 leading-tight">
                        Dashboard
                    </h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Isolasi Multi-Tenancy
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Selamat datang kembali, {{ Auth::user()->name }}!</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Period Filter --}}
                <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2" x-data="{ period: '{{ $period }}' }">
                    <select name="period" x-model="period" @change="if(period !== 'custom') $el.closest('form').submit()"
                        class="text-sm border-slate-300 rounded-lg shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-3">
                        <option value="current_month">Bulan Ini</option>
                        <option value="previous_month">Bulan Lalu</option>
                        <option value="custom">Rentang Kustom</option>
                    </select>

                    <template x-if="period === 'custom'">
                        <div class="flex items-center gap-1.5">
                            <input type="date" name="start_date" value="{{ $customStart ?? $startDate }}"
                                class="text-sm border-slate-300 rounded-lg shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-2">
                            <span class="text-slate-400 text-xs">—</span>
                            <input type="date" name="end_date" value="{{ $customEnd ?? $endDate }}"
                                class="text-sm border-slate-300 rounded-lg shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-1.5 px-2">
                            <button type="submit"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-xs">
                                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                                Terapkan
                            </button>
                        </div>
                    </template>
                </form>

                {{-- Add Transaction CTA --}}
                <a href="{{ route('transactions.create') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm hover:shadow-md">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">Tambah Transaksi</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Period indicator --}}
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <i data-lucide="calendar" class="w-4 h-4"></i>
                <span>Periode: <strong class="text-slate-700">{{ $periodLabel }}</strong></span>
                <span class="text-slate-300">|</span>
                <span>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</span>
                <span class="text-slate-300">|</span>
                <span>{{ $transactionCount }} transaksi</span>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- SUMMARY CARDS                           --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Balance --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Saldo</span>
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center">
                            <i data-lucide="wallet" class="w-4.5 h-4.5 text-emerald-700"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold {{ $totalBalanceFloat >= 0 ? 'text-slate-900' : 'text-red-600' }}">
                        @if($totalBalanceFloat < 0)
                            -Rp{{ number_format(abs($totalBalanceFloat), 0, ',', '.') }}
                        @else
                            Rp{{ number_format($totalBalanceFloat, 0, ',', '.') }}
                        @endif
                    </p>
                    <p class="text-xs text-slate-500 mt-1">{{ $activeWallets->count() }} dompet aktif</p>
                </div>

                {{-- Income --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Pemasukan</span>
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-4.5 h-4.5 text-green-700"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-green-600">
                        Rp{{ number_format((float)$totalIncome, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">{{ $periodLabel }}</p>
                </div>

                {{-- Expense --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Pengeluaran</span>
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-red-100 to-rose-100 flex items-center justify-center">
                            <i data-lucide="trending-down" class="w-4.5 h-4.5 text-red-700"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-red-600">
                        Rp{{ number_format((float)$totalExpense, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">{{ $periodLabel }}</p>
                </div>

                {{-- Net Cash Flow --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs hover:shadow-sm transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Arus Kas Bersih</span>
                        <div class="w-9 h-9 rounded-xl {{ $netCashFlowFloat >= 0 ? 'bg-gradient-to-br from-blue-100 to-indigo-100' : 'bg-gradient-to-br from-orange-100 to-red-100' }} flex items-center justify-center">
                            <i data-lucide="{{ $netCashFlowFloat >= 0 ? 'arrow-up-circle' : 'arrow-down-circle' }}" class="w-4.5 h-4.5 {{ $netCashFlowFloat >= 0 ? 'text-blue-700' : 'text-orange-700' }}"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold {{ $netCashFlowFloat >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                        @if($netCashFlowFloat < 0)
                            -Rp{{ number_format(abs($netCashFlowFloat), 0, ',', '.') }}
                        @else
                            Rp{{ number_format($netCashFlowFloat, 0, ',', '.') }}
                        @endif
                    </p>
                    <p class="text-xs text-slate-500 mt-1">{{ $periodLabel }}</p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- CHARTS ROW                              --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Income vs Expense Chart --}}
                <div class="lg:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-emerald-600"></i>
                            Pemasukan vs Pengeluaran
                        </h2>
                        <span class="text-xs text-slate-500">{{ $periodLabel }}</span>
                    </div>
                    @if(array_sum($chartData['income']) > 0 || array_sum($chartData['expense']) > 0)
                        <div class="h-64">
                            <canvas id="incomeExpenseChart"></canvas>
                        </div>
                    @else
                        <div class="h-64 flex items-center justify-center">
                            <div class="text-center">
                                <i data-lucide="bar-chart-3" class="w-12 h-12 text-slate-300 mx-auto mb-2"></i>
                                <p class="text-sm text-slate-500">Belum ada transaksi pada periode ini.</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Expense Breakdown Donut --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-red-500"></i>
                        Pengeluaran per Kategori
                    </h2>
                    @if($expenseByCategory->isNotEmpty())
                        <div class="h-48 mb-3">
                            <canvas id="expenseCategoryChart"></canvas>
                        </div>
                        <div class="space-y-1.5 max-h-36 overflow-y-auto">
                            @foreach($expenseByCategory as $cat)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                        <span class="text-slate-700 truncate max-w-[120px]">{{ $cat['name'] }}</span>
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $cat['formatted'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-48 flex items-center justify-center">
                            <div class="text-center">
                                <i data-lucide="pie-chart" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                                <p class="text-xs text-slate-500">Belum ada pengeluaran.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- WALLET BALANCES                         --}}
            {{-- ═══════════════════════════════════════ --}}
            @if($walletSummaries->isNotEmpty())
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i>
                            Saldo Dompet
                        </h2>
                        <a href="{{ route('wallets.index') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition flex items-center gap-1">
                            Lihat Semua
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        @foreach($walletSummaries as $ws)
                            <a href="{{ route('wallets.show', $ws['id']) }}"
                                class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50/30 transition group">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 group-hover:bg-emerald-100 flex items-center justify-center transition shrink-0">
                                    <i data-lucide="{{ $ws['type_icon'] }}" class="w-5 h-5 text-slate-600 group-hover:text-emerald-700 transition"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $ws['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $ws['type_label'] }}</p>
                                </div>
                                <p class="text-sm font-bold {{ $ws['balance'] >= 0 ? 'text-slate-900' : 'text-red-600' }} shrink-0">
                                    {{ $ws['formatted_balance'] }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ═══════════════════════════════════════ --}}
            {{-- BUDGET PROGRESS & SAVINGS GOALS         --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Budget Progress --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <i data-lucide="target" class="w-4 h-4 text-amber-500"></i>
                        Progres Anggaran
                        <span class="text-xs font-normal text-slate-400">({{ $periodLabel }})</span>
                    </h2>
                    @if($budgetProgress->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($budgetProgress as $bp)
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $bp['category_color'] }}"></span>
                                            <span class="text-xs font-medium text-slate-700">{{ $bp['category_name'] }}</span>
                                        </div>
                                        <span class="text-xs text-slate-500">
                                            {{ $bp['formatted_spent'] }} / {{ $bp['formatted_limit'] }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-500
                                            @if($bp['is_exceeded']) bg-red-500
                                            @elseif($bp['is_warning']) bg-amber-500
                                            @else bg-emerald-500
                                            @endif"
                                            style="width: {{ $bp['percentage'] }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-[11px] {{ $bp['is_exceeded'] ? 'text-red-600 font-semibold' : ($bp['is_warning'] ? 'text-amber-600' : 'text-slate-400') }}">
                                            {{ $bp['percentage'] }}%
                                            @if($bp['is_exceeded']) — Melebihi anggaran!
                                            @elseif($bp['is_warning']) — Hampir penuh
                                            @endif
                                        </span>
                                        <span class="text-[11px] text-slate-400">Sisa: {{ $bp['formatted_remaining'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center">
                            <i data-lucide="target" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-sm text-slate-500">Belum ada anggaran untuk periode ini.</p>
                            <p class="text-xs text-slate-400 mt-1">Anggaran membantu Anda mengendalikan pengeluaran.</p>
                        </div>
                    @endif
                </div>

                {{-- Savings Goals --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <i data-lucide="piggy-bank" class="w-4 h-4 text-purple-500"></i>
                        Target Tabungan
                    </h2>
                    @if($goalsProgress->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($goalsProgress as $gp)
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-xs font-medium text-slate-700">{{ $gp['name'] }}</span>
                                        @if($gp['target_date'])
                                            <span class="text-[11px] text-slate-400 flex items-center gap-1">
                                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                                {{ $gp['target_date'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full bg-gradient-to-r from-purple-500 to-violet-500 transition-all duration-500"
                                            style="width: {{ $gp['percentage'] }}%"></div>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="text-[11px] text-purple-600 font-medium">{{ $gp['percentage'] }}%</span>
                                        <span class="text-[11px] text-slate-400">
                                            {{ $gp['formatted_saved'] }} / {{ $gp['formatted_target'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center">
                            <i data-lucide="piggy-bank" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-sm text-slate-500">Belum ada target tabungan aktif.</p>
                            <p class="text-xs text-slate-400 mt-1">Mulai menabung untuk impian Anda.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- RECENT TRANSACTIONS                     --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-slate-500"></i>
                        Transaksi Terbaru
                    </h2>
                    <a href="{{ route('transactions.index') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition flex items-center gap-1">
                        Lihat Semua
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
                @if($recentTransactions->isNotEmpty())
                    <div class="divide-y divide-slate-100">
                        @foreach($recentTransactions as $tx)
                            <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                {{-- Icon --}}
                                <div class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center
                                    @if($tx->is_income) bg-green-100 text-green-700
                                    @elseif($tx->is_expense) bg-red-100 text-red-700
                                    @else bg-blue-100 text-blue-700
                                    @endif">
                                    <i data-lucide="{{ $tx->type_icon }}" class="w-4 h-4"></i>
                                </div>

                                {{-- Details --}}
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-slate-900 truncate">
                                        @if($tx->is_transfer)
                                            {{ $tx->wallet?->name ?? '—' }} → {{ $tx->targetWallet?->name ?? '—' }}
                                        @else
                                            {{ $tx->category?->name ?? 'Tanpa Kategori' }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{ $tx->wallet?->name ?? '—' }}
                                        @if($tx->notes) · {{ Str::limit($tx->notes, 40) }} @endif
                                    </p>
                                </div>

                                {{-- Amount --}}
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold
                                        @if($tx->is_income) text-green-600
                                        @elseif($tx->is_expense) text-red-600
                                        @else text-blue-600
                                        @endif">
                                        @if($tx->is_income) + @elseif($tx->is_expense) - @endif
                                        {{ $tx->formatted_amount }}
                                    </p>
                                    <p class="text-[11px] text-slate-400">{{ $tx->transaction_date->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <i data-lucide="receipt" class="w-12 h-12 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-sm text-slate-500">Belum ada transaksi.</p>
                        <a href="{{ route('transactions.create') }}" class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 transition">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            Buat transaksi pertama
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Chart.js CDN --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- Income vs Expense Bar/Line Chart ---
            const ieCtx = document.getElementById('incomeExpenseChart');
            if (ieCtx) {
                new Chart(ieCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [
                            {
                                label: 'Pemasukan',
                                data: @json($chartData['income']),
                                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                                borderColor: 'rgb(34, 197, 94)',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 24,
                            },
                            {
                                label: 'Pengeluaran',
                                data: @json($chartData['expense']),
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1,
                                borderRadius: 4,
                                maxBarThickness: 24,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.dataset.label + ': Rp' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { font: { size: 10 }, maxRotation: 45 }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.04)' },
                                ticks: {
                                    font: { size: 10 },
                                    callback: function(val) {
                                        if (val >= 1000000) return 'Rp' + (val / 1000000).toFixed(1) + 'jt';
                                        if (val >= 1000) return 'Rp' + (val / 1000).toFixed(0) + 'rb';
                                        return 'Rp' + val;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // --- Expense Category Donut Chart ---
            const ecCtx = document.getElementById('expenseCategoryChart');
            if (ecCtx) {
                const categoryData = @json($expenseByCategory->values());
                const defaultColors = [
                    '#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6',
                    '#3b82f6', '#6366f1', '#a855f7', '#ec4899', '#64748b'
                ];
                new Chart(ecCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryData.map(c => c.name),
                        datasets: [{
                            data: categoryData.map(c => c.total),
                            backgroundColor: categoryData.map((c, i) => c.color && c.color !== '#94a3b8' ? c.color : defaultColors[i % defaultColors.length]),
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.label + ': Rp' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
