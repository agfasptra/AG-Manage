<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display financial reports with filters, comparisons, and visualizations.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Resolve date range & period preset
        $preset = $request->query('preset', 'current_month');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($preset, $customStart, $customEnd);
        [$prevStartDate, $prevEndDate, $prevPeriodLabel] = $this->resolvePreviousPeriod($startDate, $endDate);

        // 2. Additional filters
        $walletId = $request->query('wallet_id');
        $categoryId = $request->query('category_id');
        $type = $request->query('type');

        // Available filter options
        $wallets = $user->wallets()->orderBy('name')->get();
        $categories = Category::accessibleBy($user->id)->where('is_active', true)->orderBy('name')->get();

        // Base query for current period
        $baseQuery = $user->transactions()
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($walletId, function ($q, $wid) {
                $q->where(function ($sub) use ($wid) {
                    $sub->where('wallet_id', $wid)
                        ->orWhere('target_wallet_id', $wid);
                });
            })
            ->when($categoryId, function ($q, $cid) {
                $q->where('category_id', $cid);
            })
            ->when($type && in_array($type, ['income', 'expense', 'transfer']), function ($q) use ($type) {
                $q->where('type', $type);
            });

        // Current period aggregates (Transfers strictly neutral per PRD 5.10 & 6)
        $totalIncome = (float) (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $baseQuery)->where('type', 'expense')->sum('amount');
        $totalTransfer = (float) (clone $baseQuery)->where('type', 'transfer')->sum('amount');
        $netCashFlow = (float) bcsub((string) $totalIncome, (string) $totalExpense, 2);

        // Base query for previous period (for Month-over-Month comparison)
        $prevQuery = $user->transactions()
            ->whereDate('transaction_date', '>=', $prevStartDate)
            ->whereDate('transaction_date', '<=', $prevEndDate)
            ->when($walletId, function ($q, $wid) {
                $q->where(function ($sub) use ($wid) {
                    $sub->where('wallet_id', $wid)
                        ->orWhere('target_wallet_id', $wid);
                });
            })
            ->when($categoryId, function ($q, $cid) {
                $q->where('category_id', $cid);
            })
            ->when($type && in_array($type, ['income', 'expense', 'transfer']), function ($q) use ($type) {
                $q->where('type', $type);
            });

        $prevIncome = (float) (clone $prevQuery)->where('type', 'income')->sum('amount');
        $prevExpense = (float) (clone $prevQuery)->where('type', 'expense')->sum('amount');
        $prevNetCashFlow = (float) bcsub((string) $prevIncome, (string) $prevExpense, 2);

        // Comparisons
        $comparison = [
            'prev_period_label' => $prevPeriodLabel,
            'income_diff' => $totalIncome - $prevIncome,
            'income_pct' => $prevIncome > 0 ? round((($totalIncome - $prevIncome) / $prevIncome) * 100, 1) : null,
            'expense_diff' => $totalExpense - $prevExpense,
            'expense_pct' => $prevExpense > 0 ? round((($totalExpense - $prevExpense) / $prevExpense) * 100, 1) : null,
            'net_diff' => $netCashFlow - $prevNetCashFlow,
        ];

        // 3. Expense breakdown by category
        $expenseByCategory = (clone $baseQuery)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($totalExpense) {
                $category = Category::find($row->category_id);
                $total = (float) $row->total;
                $pct = $totalExpense > 0 ? round(($total / $totalExpense) * 100, 1) : 0;

                return [
                    'name' => $category?->name ?? 'Tanpa Kategori',
                    'color' => $category?->color ?? '#94a3b8',
                    'total' => $total,
                    'formatted' => 'Rp'.number_format($total, 0, ',', '.'),
                    'percentage' => $pct,
                ];
            });

        // 4. Income breakdown by category
        $incomeByCategory = (clone $baseQuery)
            ->where('type', 'income')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($totalIncome) {
                $category = Category::find($row->category_id);
                $total = (float) $row->total;
                $pct = $totalIncome > 0 ? round(($total / $totalIncome) * 100, 1) : 0;

                return [
                    'name' => $category?->name ?? 'Tanpa Kategori',
                    'color' => $category?->color ?? '#94a3b8',
                    'total' => $total,
                    'formatted' => 'Rp'.number_format($total, 0, ',', '.'),
                    'percentage' => $pct,
                ];
            });

        // 5. Daily trend chart data
        $chartData = $this->buildDailyChartData($user, $startDate, $endDate, $walletId, $categoryId);

        // 6. Paginated transaction ledger for audit
        $transactions = (clone $baseQuery)
            ->with(['wallet', 'targetWallet', 'category'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('reports.index', compact(
            'wallets',
            'categories',
            'preset',
            'startDate',
            'endDate',
            'periodLabel',
            'customStart',
            'customEnd',
            'walletId',
            'categoryId',
            'type',
            'totalIncome',
            'totalExpense',
            'totalTransfer',
            'netCashFlow',
            'comparison',
            'expenseByCategory',
            'incomeByCategory',
            'chartData',
            'transactions'
        ));
    }

    /**
     * Export filtered transactions to CSV with formula injection protection and UTF-8 BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        $preset = $request->query('preset', 'current_month');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');
        [$startDate, $endDate] = $this->resolveDateRange($preset, $customStart, $customEnd);

        $walletId = $request->query('wallet_id');
        $categoryId = $request->query('category_id');
        $type = $request->query('type');

        // Scoped strictly to authenticated user's records
        $query = $user->transactions()
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($walletId, function ($q, $wid) {
                $q->where(function ($sub) use ($wid) {
                    $sub->where('wallet_id', $wid)
                        ->orWhere('target_wallet_id', $wid);
                });
            })
            ->when($categoryId, function ($q, $cid) {
                $q->where('category_id', $cid);
            })
            ->when($type && in_array($type, ['income', 'expense', 'transfer']), function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->with(['wallet', 'targetWallet', 'category'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $filename = 'laporan-keuangan-'.Carbon::now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel on Windows
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header Row
            fputcsv($handle, [
                'ID Transaksi',
                'Tanggal',
                'Tipe',
                'Kategori',
                'Dompet Asal',
                'Dompet Tujuan',
                'Nominal (Rp)',
                'Catatan',
            ]);

            $query->chunk(200, function ($transactions) use ($handle) {
                foreach ($transactions as $tx) {
                    $typeLabel = match ($tx->type) {
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                        'transfer' => 'Transfer',
                        default => ucfirst($tx->type),
                    };

                    fputcsv($handle, [
                        $tx->id,
                        $tx->transaction_date->format('Y-m-d'),
                        $typeLabel,
                        $this->sanitizeCsvCell($tx->category?->name ?? ($tx->is_transfer ? 'Transfer Antar-Dompet' : '')),
                        $this->sanitizeCsvCell($tx->wallet?->name ?? ''),
                        $this->sanitizeCsvCell($tx->targetWallet?->name ?? ''),
                        number_format((float) $tx->amount, 2, '.', ''),
                        $this->sanitizeCsvCell($tx->notes ?? ''),
                    ]);
                }
            });

            fclose($handle);
        }, Response::HTTP_OK, $headers);
    }

    /**
     * Sanitize cell content against CSV formula injection (PRD 5.10 & Security Rule 6).
     * Neutralizes cells starting with =, +, -, @, \t, or \r.
     */
    protected function sanitizeCsvCell(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Resolve date range preset to start and end date strings.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveDateRange(string $preset, ?string $customStart, ?string $customEnd): array
    {
        return match ($preset) {
            'previous_month' => [
                Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                Carbon::now()->subMonth()->endOfMonth()->toDateString(),
                'Bulan Lalu ('.Carbon::now()->subMonth()->translatedFormat('F Y').')',
            ],
            'last_30_days' => [
                Carbon::now()->subDays(29)->toDateString(),
                Carbon::now()->toDateString(),
                '30 Hari Terakhir',
            ],
            'current_year' => [
                Carbon::now()->startOfYear()->toDateString(),
                Carbon::now()->endOfYear()->toDateString(),
                'Tahun '.Carbon::now()->year,
            ],
            'custom' => [
                $customStart ?: Carbon::now()->startOfMonth()->toDateString(),
                $customEnd ?: Carbon::now()->endOfMonth()->toDateString(),
                'Rentang Kustom',
            ],
            default => [ // current_month
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString(),
                'Bulan Ini ('.Carbon::now()->translatedFormat('F Y').')',
            ],
        };
    }

    /**
     * Resolve corresponding previous period for comparison.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolvePreviousPeriod(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $days = $start->diffInDays($end) + 1;

        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        return [
            $prevStart->toDateString(),
            $prevEnd->toDateString(),
            $prevStart->translatedFormat('d M Y').' - '.$prevEnd->translatedFormat('d M Y'),
        ];
    }

    /**
     * Build daily time-series chart data for the period.
     *
     * @return array{labels: array<string>, income: array<float>, expense: array<float>}
     */
    private function buildDailyChartData($user, string $startDate, string $endDate, $walletId = null, $categoryId = null): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $labels = [];
        $incomeData = [];
        $expenseData = [];

        $dailyIncome = $user->transactions()
            ->where('type', 'income')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($walletId, function ($q, $wid) {
                $q->where('wallet_id', $wid);
            })
            ->when($categoryId, function ($q, $cid) {
                $q->where('category_id', $cid);
            })
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->groupByRaw('DATE(transaction_date)')
            ->pluck('total', 'date');

        $dailyExpense = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->when($walletId, function ($q, $wid) {
                $q->where('wallet_id', $wid);
            })
            ->when($categoryId, function ($q, $cid) {
                $q->where('category_id', $cid);
            })
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->groupByRaw('DATE(transaction_date)')
            ->pluck('total', 'date');

        $current = $start->copy();
        // Limit daily points to 60 for performance and readable chart
        $stepDays = $start->diffInDays($end) > 60 ? ceil($start->diffInDays($end) / 30) : 1;

        while ($current->lte($end)) {
            $dateKey = $current->toDateString();
            $labels[] = $current->format('d M');
            $incomeData[] = (float) ($dailyIncome[$dateKey] ?? 0);
            $expenseData[] = (float) ($dailyExpense[$dateKey] ?? 0);
            $current->addDays($stepDays);
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }
}
