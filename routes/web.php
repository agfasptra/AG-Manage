<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalContributionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $dbConnected = false;
    $dbName = '';
    $driver = '';
    try {
        DB::connection()->getPdo();
        $dbConnected = true;
        $dbName = DB::connection()->getDatabaseName();
        $driver = DB::connection()->getDriverName();
    } catch (Throwable $e) {
        $dbConnected = false;
    }

    return view('welcome', compact('dbConnected', 'dbName', 'driver'));
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Wallets
    Route::resource('wallets', WalletController::class);
    Route::patch('/wallets/{wallet}/toggle-archive', [WalletController::class, 'toggleArchive'])
        ->name('wallets.toggle-archive');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('/categories/{category}/toggle-active', [CategoryController::class, 'toggleActive'])
        ->name('categories.toggle-active');

    // Transactions
    Route::resource('transactions', TransactionController::class);

    // Budgets
    Route::resource('budgets', BudgetController::class)->except(['show']);

    // Savings Goals & Contributions
    Route::resource('savings-goals', SavingsGoalController::class);
    Route::patch('/savings-goals/{savings_goal}/toggle-status', [SavingsGoalController::class, 'toggleStatus'])
        ->name('savings-goals.toggle-status');
    Route::post('/savings-goals/{savings_goal}/contributions', [GoalContributionController::class, 'store'])
        ->name('goal-contributions.store');
    Route::delete('/goal-contributions/{contribution}', [GoalContributionController::class, 'destroy'])
        ->name('goal-contributions.destroy');

    // Reports and CSV Export
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});

require __DIR__.'/auth.php';
