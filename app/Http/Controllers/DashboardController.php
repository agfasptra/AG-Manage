<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with real financial data.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // --- Period filter ---
        $period = $request->query('period', 'current_month');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

        [$startDate, $endDate, $periodLabel] = $this->resolvePeriod($period, $customStart, $customEnd);

        // --- Total balance across active wallets ---
        $activeWallets = $user->wallets()->active()->orderBy('name')->get();
        $totalBalance = '0.00';
        foreach ($activeWallets as $wallet) {
            $totalBalance = bcadd($totalBalance, $wallet->current_balance, 2);
        }
        $totalBalanceFloat = (float) $totalBalance;

        // --- Period-based income & expense (transfers excluded per PRD) ---
        $periodQuery = $user->transactions()
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        $totalIncome = (string) (clone $periodQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (string) (clone $periodQuery)->where('type', 'expense')->sum('amount');
        $netCashFlow = bcsub($totalIncome, $totalExpense, 2);
        $netCashFlowFloat = (float) $netCashFlow;

        // --- Expense breakdown by category ---
        $expenseByCategory = (clone $periodQuery)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $category = Category::find($row->category_id);

                return [
                    'name' => $category?->name ?? 'Tanpa Kategori',
                    'color' => $category?->color ?? '#94a3b8',
                    'total' => (float) $row->total,
                    'formatted' => 'Rp'.number_format((float) $row->total, 0, ',', '.'),
                ];
            });

        // --- Income breakdown by category ---
        $incomeByCategory = (clone $periodQuery)
            ->where('type', 'income')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $category = Category::find($row->category_id);

                return [
                    'name' => $category?->name ?? 'Tanpa Kategori',
                    'color' => $category?->color ?? '#94a3b8',
                    'total' => (float) $row->total,
                    'formatted' => 'Rp'.number_format((float) $row->total, 0, ',', '.'),
                ];
            });

        // --- Daily income vs expense chart data (last 30 days or period) ---
        $chartData = $this->buildDailyChartData($user, $startDate, $endDate);

        // --- Budget progress for current period ---
        $budgetProgress = $this->buildBudgetProgress($user, $startDate, $endDate);

        // --- Savings goals progress ---
        $goalsProgress = $this->buildGoalsProgress($user);

        // --- Recent transactions ---
        $recentTransactions = $user->transactions()
            ->with(['wallet', 'targetWallet', 'category'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // --- Wallet summaries ---
        $walletSummaries = $activeWallets->map(function (Wallet $wallet) {
            $balance = (float) $wallet->current_balance;

            return [
                'id' => $wallet->id,
                'name' => $wallet->name,
                'type' => $wallet->type,
                'type_label' => $wallet->type_label,
                'type_icon' => $wallet->type_icon,
                'balance' => $balance,
                'formatted_balance' => $wallet->formatted_balance,
            ];
        });

        // --- Transaction count for period ---
        $transactionCount = (clone $periodQuery)->count();

        return view('dashboard', compact(
            'activeWallets',
            'totalBalance',
            'totalBalanceFloat',
            'totalIncome',
            'totalExpense',
            'netCashFlow',
            'netCashFlowFloat',
            'expenseByCategory',
            'incomeByCategory',
            'chartData',
            'budgetProgress',
            'goalsProgress',
            'recentTransactions',
            'walletSummaries',
            'transactionCount',
            'period',
            'startDate',
            'endDate',
            'periodLabel',
            'customStart',
            'customEnd'
        ));
    }

    /**
     * Resolve period filter to start and end dates.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolvePeriod(string $period, ?string $customStart, ?string $customEnd): array
    {
        return match ($period) {
            'previous_month' => [
                Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                Carbon::now()->subMonth()->endOfMonth()->toDateString(),
                Carbon::now()->subMonth()->translatedFormat('F Y'),
            ],
            'custom' => [
                $customStart ?: Carbon::now()->startOfMonth()->toDateString(),
                $customEnd ?: Carbon::now()->endOfMonth()->toDateString(),
                'Kustom',
            ],
            default => [ // current_month
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString(),
                Carbon::now()->translatedFormat('F Y'),
            ],
        };
    }

    /**
     * Build daily income vs expense chart data for the period.
     *
     * @return array{labels: array<string>, income: array<float>, expense: array<float>}
     */
    private function buildDailyChartData($user, string $startDate, string $endDate): array
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
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->groupByRaw('DATE(transaction_date)')
            ->pluck('total', 'date');

        $dailyExpense = $user->transactions()
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->selectRaw('DATE(transaction_date) as date, SUM(amount) as total')
            ->groupByRaw('DATE(transaction_date)')
            ->pluck('total', 'date');

        $current = $start->copy();
        while ($current->lte($end)) {
            $dateKey = $current->toDateString();
            $labels[] = $current->format('d M');
            $incomeData[] = (float) ($dailyIncome[$dateKey] ?? 0);
            $expenseData[] = (float) ($dailyExpense[$dateKey] ?? 0);
            $current->addDay();
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }

    /**
     * Build budget progress for the current period month/year.
     *
     * @return Collection
     */
    private function buildBudgetProgress($user, string $startDate, string $endDate)
    {
        $start = Carbon::parse($startDate);
        $month = $start->month;
        $year = $start->year;

        $budgets = $user->budgets()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with('category')
            ->get();

        return $budgets->map(function (Budget $budget) use ($user, $startDate, $endDate) {
            $spent = (float) $user->transactions()
                ->where('type', 'expense')
                ->where('category_id', $budget->category_id)
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate)
                ->sum('amount');

            $limit = (float) $budget->amount;
            $percentage = $limit > 0 ? min(round(($spent / $limit) * 100, 1), 100) : 0;
            $remaining = max($limit - $spent, 0);

            return [
                'id' => $budget->id,
                'category_name' => $budget->category?->name ?? 'Tanpa Kategori',
                'category_color' => $budget->category?->color ?? '#94a3b8',
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'formatted_limit' => 'Rp'.number_format($limit, 0, ',', '.'),
                'formatted_spent' => 'Rp'.number_format($spent, 0, ',', '.'),
                'formatted_remaining' => 'Rp'.number_format($remaining, 0, ',', '.'),
                'is_warning' => $percentage >= 80 && $percentage < 100,
                'is_exceeded' => $spent >= $limit && $limit > 0,
            ];
        });
    }

    /**
     * Build savings goals progress.
     *
     * @return Collection
     */
    private function buildGoalsProgress($user)
    {
        $goals = $user->savingsGoals()
            ->where('status', 'active')
            ->with('contributions')
            ->orderBy('target_date')
            ->get();

        return $goals->map(function (SavingsGoal $goal) {
            $saved = (float) $goal->contributions->sum('amount');
            $target = (float) $goal->target_amount;
            $percentage = $target > 0 ? min(round(($saved / $target) * 100, 1), 100) : 0;
            $remaining = max($target - $saved, 0);

            return [
                'id' => $goal->id,
                'name' => $goal->name,
                'target' => $target,
                'saved' => $saved,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'target_date' => $goal->target_date?->translatedFormat('d M Y'),
                'formatted_target' => 'Rp'.number_format($target, 0, ',', '.'),
                'formatted_saved' => 'Rp'.number_format($saved, 0, ',', '.'),
                'formatted_remaining' => 'Rp'.number_format($remaining, 0, ',', '.'),
            ];
        });
    }
}
