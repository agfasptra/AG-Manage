<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;

class InsightService
{
    /**
     * Generate rule-based financial insights for a user in a given month/year.
     *
     * @return array<int, array{type: string, title: string, message: string, icon: string, level: string}>
     */
    public function getMonthlyInsights(User $user, int $month, int $year): array
    {
        $insights = [];

        $currentStart = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $currentEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $prevStart = Carbon::createFromDate($year, $month, 1)->subMonth()->startOfMonth()->toDateString();
        $prevEnd = Carbon::createFromDate($year, $month, 1)->subMonth()->endOfMonth()->toDateString();

        // 1. Budget Warnings and Exceeded Alerts
        $budgets = Budget::where('user_id', $user->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with('category')
            ->get();

        foreach ($budgets as $budget) {
            $catName = $budget->category?->name ?? 'Kategori';
            if ($budget->is_exceeded) {
                $overAmount = $budget->spent_amount - (float) $budget->amount;
                $insights[] = [
                    'type' => 'budget_exceeded',
                    'title' => "Anggaran {$catName} Melebihi Batas!",
                    'message' => "Pengeluaran untuk {$catName} telah mencapai {$budget->formatted_spent}, melebihi batas anggaran ({$budget->formatted_amount}) sebesar Rp".number_format($overAmount, 0, ',', '.').'.',
                    'icon' => 'alert-circle',
                    'level' => 'danger',
                ];
            } elseif ($budget->is_warning) {
                $insights[] = [
                    'type' => 'budget_warning',
                    'title' => "Waspada Anggaran {$catName}",
                    'message' => "Pengeluaran {$catName} telah mencapai {$budget->percentage_used}% ({$budget->formatted_spent} dari {$budget->formatted_amount}). Sisa kuota: {$budget->formatted_remaining}.",
                    'icon' => 'alert-triangle',
                    'level' => 'warning',
                ];
            }
        }

        // 2. Category Spending Comparison (Current Month vs Previous Month)
        $currentSpending = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $currentStart)
            ->whereDate('transaction_date', '<=', $currentEnd)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $prevSpending = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $prevStart)
            ->whereDate('transaction_date', '<=', $prevEnd)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        foreach ($currentSpending as $catId => $currTotal) {
            $prevTotal = (float) ($prevSpending[$catId] ?? 0);
            if ($prevTotal > 0) {
                $category = Category::find($catId);
                $catName = $category?->name ?? 'Kategori';
                $diff = (float) $currTotal - $prevTotal;
                $percentChange = round((abs($diff) / $prevTotal) * 100, 1);

                if ($diff > 0 && $percentChange >= 20) {
                    $insights[] = [
                        'type' => 'spending_increase',
                        'title' => "Kenaikan Pengeluaran {$catName}",
                        'message' => "Pengeluaran {$catName} bulan ini naik {$percentChange}% dibanding bulan lalu (Rp".number_format((float) $currTotal, 0, ',', '.').' vs Rp'.number_format($prevTotal, 0, ',', '.').').',
                        'icon' => 'trending-up',
                        'level' => 'warning',
                    ];
                } elseif ($diff < 0 && $percentChange >= 20) {
                    $insights[] = [
                        'type' => 'spending_decrease',
                        'title' => "Penghematan {$catName}",
                        'message' => "Bagus! Pengeluaran {$catName} berhasil dihemat {$percentChange}% dibanding bulan lalu.",
                        'icon' => 'trending-down',
                        'level' => 'success',
                    ];
                }
            }
        }

        // 3. Savings Goal Milestones
        $activeGoals = SavingsGoal::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeGoals as $goal) {
            if ($goal->is_target_reached) {
                $insights[] = [
                    'type' => 'goal_completed',
                    'title' => 'Target Tabungan Tercapai! 🎉',
                    'message' => "Selamat! Target \"{$goal->name}\" telah terkumpul 100% ({$goal->formatted_saved}). Anda dapat menandainya sebagai selesai.",
                    'icon' => 'award',
                    'level' => 'success',
                ];
            } elseif ($goal->progress_percentage >= 75) {
                $insights[] = [
                    'type' => 'goal_milestone',
                    'title' => 'Target Tabungan Hampir Selesai',
                    'message' => "\"{$goal->name}\" sudah terkumpul {$goal->progress_percentage}% ({$goal->formatted_saved} dari {$goal->formatted_target_amount}). Sisa: {$goal->formatted_remaining}.",
                    'icon' => 'piggy-bank',
                    'level' => 'info',
                ];
            }
        }

        // 4. Monthly Net Cash Flow Insight
        $currentIncome = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereDate('transaction_date', '>=', $currentStart)
            ->whereDate('transaction_date', '<=', $currentEnd)
            ->sum('amount');

        $totalExpense = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $currentStart)
            ->whereDate('transaction_date', '<=', $currentEnd)
            ->sum('amount');

        if ($currentIncome > 0 && $totalExpense > 0) {
            $net = $currentIncome - $totalExpense;
            if ($net > 0) {
                $savingsRate = round(($net / $currentIncome) * 100, 1);
                $insights[] = [
                    'type' => 'positive_cashflow',
                    'title' => 'Arus Kas Positif (Surplus)',
                    'message' => "Bulan ini Anda berhasil menyisihkan {$savingsRate}% dari total pemasukan (surplus Rp".number_format($net, 0, ',', '.').').',
                    'icon' => 'shield-check',
                    'level' => 'success',
                ];
            } elseif ($net < 0) {
                $insights[] = [
                    'type' => 'negative_cashflow',
                    'title' => 'Defisit Arus Kas Bulan Ini',
                    'message' => 'Total pengeluaran melebihi pemasukan sebesar Rp'.number_format(abs($net), 0, ',', '.').'. Tinjau kembali pos pengeluaran yang tidak mendesak.',
                    'icon' => 'alert-triangle',
                    'level' => 'danger',
                ];
            }
        }

        return $insights;
    }

    /**
     * Calculate Safe-to-Spend estimate per PRD 5.9:
     * Total available wallet balance - unspent remaining budget commitments for current month.
     *
     * @return array{
     *     total_balance: float,
     *     budget_commitments: float,
     *     safe_to_spend: float,
     *     formatted_total_balance: string,
     *     formatted_budget_commitments: string,
     *     formatted_safe_to_spend: string,
     *     explanation: string
     * }
     */
    public function calculateSafeToSpend(User $user, int $month, int $year): array
    {
        // 1. Total balance across all active wallets
        $activeWallets = Wallet::where('user_id', $user->id)->active()->get();
        $totalBalance = '0.00';
        foreach ($activeWallets as $wallet) {
            $totalBalance = bcadd($totalBalance, $wallet->current_balance, 2);
        }
        $totalBalanceFloat = (float) $totalBalance;

        // 2. Unspent budget commitments for current month
        $budgets = Budget::where('user_id', $user->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->get();

        $budgetCommitments = 0.00;
        foreach ($budgets as $budget) {
            $budgetCommitments += $budget->remaining_amount;
        }

        // 3. Safe to spend = available balance - budget commitments
        $safeToSpend = max($totalBalanceFloat - $budgetCommitments, 0);

        return [
            'total_balance' => $totalBalanceFloat,
            'budget_commitments' => $budgetCommitments,
            'safe_to_spend' => $safeToSpend,
            'formatted_total_balance' => ($totalBalanceFloat < 0 ? '-Rp' : 'Rp').number_format(abs($totalBalanceFloat), 0, ',', '.'),
            'formatted_budget_commitments' => 'Rp'.number_format($budgetCommitments, 0, ',', '.'),
            'formatted_safe_to_spend' => 'Rp'.number_format($safeToSpend, 0, ',', '.'),
            'explanation' => 'Estimasi dana bebas belanja setelah mengamankan sisa komitmen anggaran belanja bulan ini: Total Saldo Dompet Aktif dikurangi Sisa Kuota Anggaran.',
        ];
    }
}
