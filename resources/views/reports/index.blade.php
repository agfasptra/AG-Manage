<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="font-bold text-xl text-slate-900 leading-tight">Laporan Keuangan & Analisis</h1>
                <p class="text-xs text-slate-500 mt-0.5">Analisis arus kas, tren pengeluaran, perbandingan periode, dan ekspor data</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.export', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition shadow-sm hover:shadow-md">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Ekspor CSV</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ═══════════════════════════════════════ --}}
            {{-- COMPREHENSIVE FILTER TOOLBAR            --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs" x-data="{ preset: '{{ $preset }}' }">
                <form method="GET" action="{{ route('reports.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                        {{-- Preset Period --}}
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Periode</label>
                            <select name="preset" x-model="preset" class="w-full text-xs border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                <option value="current_month">Bulan Ini</option>
                                <option value="previous_month">Bulan Lalu</option>
                                <option value="last_30_days">30 Hari Terakhir</option>
                                <option value="current_year">Tahun Berjalan</option>
                                <option value="custom">Rentang Tanggal Kustom</option>
                            </select>
                        </div>

                        {{-- Wallet Filter --}}
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Dompet</label>
                            <select name="wallet_id" class="w-full text-xs border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                <option value="">Semua Dompet</option>
                                @foreach ($wallets as $w)
                                    <option value="{{ $w->id }}" {{ $walletId == $w->id ? 'selected' : '' }}>
                                        {{ $w->name }} ({{ $w->type_label }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Category Filter --}}
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Kategori</label>
                            <select name="category_id" class="w-full text-xs border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }} ({{ $cat->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Type Filter --}}
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Tipe Transaksi</label>
                            <select name="type" class="w-full text-xs border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-2">
                                <option value="">Semua Tipe</option>
                                <option value="income" {{ $type === 'income' ? 'selected' : '' }}>Hanya Pemasukan</option>
                                <option value="expense" {{ $type === 'expense' ? 'selected' : '' }}>Hanya Pengeluaran</option>
                                <option value="transfer" {{ $type === 'transfer' ? 'selected' : '' }}>Hanya Transfer Antar-Dompet</option>
                            </select>
                        </div>
                    </div>

                    {{-- Custom Date Inputs (Conditional) --}}
                    <div x-show="preset === 'custom'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-100 text-xs">
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Tanggal Mulai</label>
                            <input type="date" name="start_date" value="{{ $customStart ?? $startDate }}"
                                   class="w-full text-xs border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-2" />
                        </div>
                        <div>
                            <label class="font-semibold text-slate-700 block mb-1">Tanggal Selesai</label>
                            <input type="date" name="end_date" value="{{ $customEnd ?? $endDate }}"
                                   class="w-full text-xs border-slate-300 rounded-xl shadow-xs focus:border-emerald-500 focus:ring-emerald-500 py-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                            Periode Aktif: <strong class="text-slate-800">{{ $periodLabel }}</strong> ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})
                        </span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('reports.index') }}" class="px-3 py-1.5 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                                Reset
                            </a>
                            <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition shadow-xs flex items-center gap-1">
                                <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- SUMMARY CARDS WITH MOM COMPARISONS      --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Income --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pemasukan</span>
                        <div class="w-9 h-9 rounded-xl bg-green-50 text-green-600 flex items-center justify-center">
                            <i data-lucide="arrow-down-left" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-green-600 mt-2 tracking-tight">
                        Rp{{ number_format($totalIncome, 0, ',', '.') }}
                    </p>
                    <div class="mt-2 flex items-center gap-1 text-[11px]">
                        @if ($comparison['income_pct'] !== null)
                            @if ($comparison['income_diff'] >= 0)
                                <span class="font-bold text-green-600 flex items-center">
                                    <i data-lucide="trending-up" class="w-3.5 h-3.5 mr-0.5"></i>
                                    +{{ $comparison['income_pct'] }}%
                                </span>
                            @else
                                <span class="font-bold text-rose-600 flex items-center">
                                    <i data-lucide="trending-down" class="w-3.5 h-3.5 mr-0.5"></i>
                                    {{ $comparison['income_pct'] }}%
                                </span>
                            @endif
                            <span class="text-slate-400">vs periode lalu</span>
                        @else
                            <span class="text-slate-400">Belum ada data periode lalu</span>
                        @endif
                    </div>
                </div>

                {{-- Total Expense --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengeluaran</span>
                        <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                            <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-red-600 mt-2 tracking-tight">
                        Rp{{ number_format($totalExpense, 0, ',', '.') }}
                    </p>
                    <div class="mt-2 flex items-center gap-1 text-[11px]">
                        @if ($comparison['expense_pct'] !== null)
                            @if ($comparison['expense_diff'] <= 0)
                                <span class="font-bold text-green-600 flex items-center">
                                    <i data-lucide="trending-down" class="w-3.5 h-3.5 mr-0.5"></i>
                                    Hemat {{ abs($comparison['expense_pct']) }}%
                                </span>
                            @else
                                <span class="font-bold text-rose-600 flex items-center">
                                    <i data-lucide="trending-up" class="w-3.5 h-3.5 mr-0.5"></i>
                                    Naik {{ $comparison['expense_pct'] }}%
                                </span>
                            @endif
                            <span class="text-slate-400">vs periode lalu</span>
                        @else
                            <span class="text-slate-400">Belum ada data periode lalu</span>
                        @endif
                    </div>
                </div>

                {{-- Net Cash Flow --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Arus Kas Bersih</span>
                        <div class="w-9 h-9 rounded-xl {{ $netCashFlow >= 0 ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }} flex items-center justify-center">
                            <i data-lucide="{{ $netCashFlow >= 0 ? 'shield-check' : 'alert-circle' }}" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold {{ $netCashFlow >= 0 ? 'text-blue-600' : 'text-orange-600' }} mt-2 tracking-tight">
                        @if ($netCashFlow < 0)
                            -Rp{{ number_format(abs($netCashFlow), 0, ',', '.') }}
                        @else
                            Rp{{ number_format($netCashFlow, 0, ',', '.') }}
                        @endif
                    </p>
                    <div class="mt-2 flex items-center gap-1 text-[11px]">
                        <span class="font-bold {{ $comparison['net_diff'] >= 0 ? 'text-blue-600' : 'text-orange-600' }}">
                            {{ $comparison['net_diff'] >= 0 ? '+Rp' : '-Rp' }}{{ number_format(abs($comparison['net_diff']), 0, ',', '.') }}
                        </span>
                        <span class="text-slate-400">vs periode lalu</span>
                    </div>
                </div>

                {{-- Total Transfer --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Transfer Antar-Dompet</span>
                        <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                            <i data-lucide="arrow-left-right" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-slate-800 mt-2 tracking-tight">
                        Rp{{ number_format($totalTransfer, 0, ',', '.') }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-2">
                        *Netral (di luar pemasukan & pengeluaran)
                    </p>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- CHARTS ROW                              --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Income vs Expense Trend Bar Chart --}}
                <div class="lg:col-span-2 bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="bar-chart-3" class="w-4 h-4 text-emerald-600"></i>
                            Tren Pemasukan vs Pengeluaran
                        </h2>
                        <span class="text-xs text-slate-400">{{ $periodLabel }}</span>
                    </div>
                    @if (array_sum($chartData['income']) > 0 || array_sum($chartData['expense']) > 0)
                        <div class="h-64">
                            <canvas id="reportTrendChart"></canvas>
                        </div>
                    @else
                        <div class="h-64 flex items-center justify-center text-center">
                            <div>
                                <i data-lucide="bar-chart-2" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                                <p class="text-xs text-slate-500">Tidak ada data transaksi pada rentang waktu ini.</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Category Breakdown Donut Chart --}}
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-4">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-red-500"></i>
                        Distribusi Pengeluaran
                    </h2>
                    @if ($expenseByCategory->isNotEmpty())
                        <div class="h-44 mb-3">
                            <canvas id="reportCategoryChart"></canvas>
                        </div>
                        <div class="space-y-1.5 max-h-40 overflow-y-auto">
                            @foreach ($expenseByCategory as $cat)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['color'] }}"></span>
                                        <span class="text-slate-700 truncate max-w-[120px]">{{ $cat['name'] }}</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="font-bold text-slate-900">{{ $cat['formatted'] }}</span>
                                        <span class="text-[10px] text-slate-400 ml-1">({{ $cat['percentage'] }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-44 flex items-center justify-center text-center">
                            <div>
                                <i data-lucide="pie-chart" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                                <p class="text-xs text-slate-500">Belum ada pengeluaran pada periode ini.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- CATEGORY BREAKDOWN TABLE                --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs">
                <h2 class="text-base font-bold text-slate-900 mb-4">Rincian Pengeluaran per Kategori</h2>
                @if ($expenseByCategory->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                    <th class="py-3 px-3">Kategori</th>
                                    <th class="py-3 px-3">Total Pengeluaran</th>
                                    <th class="py-3 px-3">Porsi dari Total</th>
                                    <th class="py-3 px-3">Visualisasi Proporsi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($expenseByCategory as $c)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-3 font-semibold text-slate-900 flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $c['color'] }}"></span>
                                            <span>{{ $c['name'] }}</span>
                                        </td>
                                        <td class="py-3 px-3 font-bold text-slate-900">
                                            {{ $c['formatted'] }}
                                        </td>
                                        <td class="py-3 px-3 font-medium text-slate-600">
                                            {{ $c['percentage'] }}%
                                        </td>
                                        <td class="py-3 px-3 w-48">
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="h-2 rounded-full" style="width: {{ $c['percentage'] }}%; background-color: {{ $c['color'] }}"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-4 text-center">Tidak ada rincian pengeluaran untuk ditampilkan.</p>
                @endif
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- TRANSACTION AUDIT LEDGER TABLE          --}}
            {{-- ═══════════════════════════════════════ --}}
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Buku Kas Transaksi (Audit Ledger)</h2>
                        <p class="text-xs text-slate-500">Seluruh transaksi yang masuk dalam cakupan laporan</p>
                    </div>
                    <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                        {{ $transactions->total() }} Transaksi
                    </span>
                </div>

                @if ($transactions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-semibold">
                                    <th class="py-3 px-3">Tanggal</th>
                                    <th class="py-3 px-3">Tipe</th>
                                    <th class="py-3 px-3">Kategori</th>
                                    <th class="py-3 px-3">Dompet</th>
                                    <th class="py-3 px-3">Nominal</th>
                                    <th class="py-3 px-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @foreach ($transactions as $tx)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="py-3 px-3 font-medium text-slate-900 whitespace-nowrap">
                                            {{ $tx->transaction_date->format('d/m/Y') }}
                                        </td>
                                        <td class="py-3 px-3">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold
                                                @if ($tx->is_income) bg-green-50 text-green-700 border border-green-200
                                                @elseif ($tx->is_expense) bg-red-50 text-red-700 border border-red-200
                                                @else bg-blue-50 text-blue-700 border border-blue-200
                                                @endif">
                                                {{ $tx->type_label }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            @if ($tx->is_transfer)
                                                <span class="text-slate-400 italic">Transfer Antar-Dompet</span>
                                            @else
                                                <span class="font-medium text-slate-800">{{ $tx->category?->name ?? 'Tanpa Kategori' }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3">
                                            @if ($tx->is_transfer)
                                                <span>{{ $tx->wallet?->name }} → {{ $tx->targetWallet?->name }}</span>
                                            @else
                                                <span>{{ $tx->wallet?->name }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 font-bold whitespace-nowrap
                                            @if ($tx->is_income) text-green-600
                                            @elseif ($tx->is_expense) text-red-600
                                            @else text-blue-600
                                            @endif">
                                            @if ($tx->is_income) + @elseif ($tx->is_expense) - @endif
                                            {{ $tx->formatted_amount }}
                                        </td>
                                        <td class="py-3 px-3 text-slate-500 max-w-xs truncate">
                                            {{ $tx->notes ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                @else
                    <div class="text-center py-10">
                        <i data-lucide="receipt" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-500">Tidak ada transaksi yang cocok dengan filter yang dipilih.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Chart.js Scripts --}}
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Trend Chart
            const trendCtx = document.getElementById('reportTrendChart');
            if (trendCtx && window.Chart) {
                new Chart(trendCtx, {
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
                            },
                            {
                                label: 'Pengeluaran',
                                data: @json($chartData['expense']),
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1,
                                borderRadius: 4,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top', labels: { usePointStyle: true, font: { size: 11 } } },
                            tooltip: {
                                callbacks: {
                                    label: function(ctx) {
                                        return ctx.dataset.label + ': Rp' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 10 },
                                    callback: function(v) {
                                        if (v >= 1000000) return 'Rp' + (v / 1000000).toFixed(1) + 'jt';
                                        if (v >= 1000) return 'Rp' + (v / 1000).toFixed(0) + 'rb';
                                        return 'Rp' + v;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Category Donut Chart
            const catCtx = document.getElementById('reportCategoryChart');
            if (catCtx && window.Chart) {
                const catData = @json($expenseByCategory->values());
                const defaultColors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#3b82f6', '#6366f1', '#a855f7', '#ec4899', '#64748b'];

                new Chart(catCtx, {
                    type: 'doughnut',
                    data: {
                        labels: catData.map(c => c.name),
                        datasets: [{
                            data: catData.map(c => c.total),
                            backgroundColor: catData.map((c, i) => c.color && c.color !== '#94a3b8' ? c.color : defaultColors[i % defaultColors.length]),
                            borderWidth: 2,
                            borderColor: '#fff',
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
