<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Services\InsightService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        protected InsightService $insightService
    ) {}

    /**
     * Display a listing of user's budgets for the selected period.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        // Fetch user's budgets for the period
        $budgets = $user->budgets()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with('category')
            ->get();

        // Calculate totals across budgets
        $totalBudget = 0.00;
        $totalSpent = 0.00;
        foreach ($budgets as $budget) {
            $totalBudget += (float) $budget->amount;
            $totalSpent += $budget->spent_amount;
        }
        $totalRemaining = max($totalBudget - $totalSpent, 0);
        $overallPercentage = $totalBudget > 0 ? min(round(($totalSpent / $totalBudget) * 100, 1), 100) : 0;

        // Fetch rule-based insights and safe-to-spend
        $insights = $this->insightService->getMonthlyInsights($user, $month, $year);
        $safeToSpend = $this->insightService->calculateSafeToSpend($user, $month, $year);

        $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

        return view('budgets.index', compact(
            'budgets',
            'month',
            'year',
            'periodLabel',
            'totalBudget',
            'totalSpent',
            'totalRemaining',
            'overallPercentage',
            'insights',
            'safeToSpend'
        ));
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $month = (int) $request->query('month', Carbon::now()->month);
        $year = (int) $request->query('year', Carbon::now()->year);

        // Existing budgeted categories for this month/year
        $existingCategoryIds = $user->budgets()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->pluck('category_id')
            ->toArray();

        // Available expense categories
        $categories = Category::accessibleBy($user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereNotIn('id', $existingCategoryIds)
            ->orderBy('name')
            ->get();

        if ($categories->isEmpty()) {
            return redirect()
                ->route('budgets.index', ['month' => $month, 'year' => $year])
                ->with('warning', 'Semua kategori pengeluaran aktif sudah memiliki anggaran untuk periode ini.');
        }

        $periodLabel = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');

        return view('budgets.create', compact('categories', 'month', 'year', 'periodLabel'));
    }

    /**
     * Store a newly created budget in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    $query->where(function ($sub) use ($user) {
                        $sub->where('user_id', $user->id)
                            ->orWhereNull('user_id');
                    })
                        ->where('type', 'expense')
                        ->where('is_active', true);
                }),
                Rule::unique('budgets')->where(function ($query) use ($user, $request) {
                    $query->where('user_id', $user->id)
                        ->where('period_month', $request->input('period_month'))
                        ->where('period_year', $request->input('period_year'));
                }),
            ],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
        ], [
            'category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid atau bukan kategori pengeluaran aktif.',
            'category_id.unique' => 'Anggaran untuk kategori ini pada periode tersebut sudah ada.',
            'period_month.required' => 'Bulan anggaran wajib ditentukan.',
            'period_year.required' => 'Tahun anggaran wajib ditentukan.',
            'amount.required' => 'Jumlah anggaran wajib diisi.',
            'amount.numeric' => 'Jumlah anggaran harus berupa angka.',
            'amount.min' => 'Jumlah anggaran minimal Rp1.000.',
            'amount.max' => 'Jumlah anggaran melebihi batas maksimum.',
        ]);

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'amount' => $validated['amount'],
        ]);

        return redirect()
            ->route('budgets.index', ['month' => $budget->period_month, 'year' => $budget->period_year])
            ->with('success', 'Anggaran berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Budget $budget): View
    {
        Gate::authorize('update', $budget);

        $periodLabel = Carbon::createFromDate($budget->period_year, $budget->period_month, 1)->translatedFormat('F Y');

        return view('budgets.edit', compact('budget', 'periodLabel'));
    }

    /**
     * Update the specified budget in storage.
     */
    public function update(Request $request, Budget $budget): RedirectResponse
    {
        Gate::authorize('update', $budget);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
        ], [
            'amount.required' => 'Jumlah anggaran wajib diisi.',
            'amount.numeric' => 'Jumlah anggaran harus berupa angka.',
            'amount.min' => 'Jumlah anggaran minimal Rp1.000.',
            'amount.max' => 'Jumlah anggaran melebihi batas maksimum.',
        ]);

        $budget->update([
            'amount' => $validated['amount'],
        ]);

        return redirect()
            ->route('budgets.index', ['month' => $budget->period_month, 'year' => $budget->period_year])
            ->with('success', 'Anggaran berhasil diperbarui.');
    }

    /**
     * Remove the specified budget from storage.
     */
    public function destroy(Budget $budget): RedirectResponse
    {
        Gate::authorize('delete', $budget);

        $month = $budget->period_month;
        $year = $budget->period_year;

        $budget->delete();

        return redirect()
            ->route('budgets.index', ['month' => $month, 'year' => $year])
            ->with('success', 'Anggaran berhasil dihapus.');
    }
}
