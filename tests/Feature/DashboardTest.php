<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Unauthenticated guest must be redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Authenticated user can load the dashboard with 200 OK.
     */
    public function test_authenticated_user_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertSee('Dashboard');
        $response->assertSee($this->user->name);
    }

    /**
     * Dashboard displays total current balance across active wallets only (excluding archived).
     */
    public function test_dashboard_displays_total_balance_across_active_wallets(): void
    {
        // Active wallet 1: 500,000 opening balance
        $wallet1 = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'BCA Utama',
            'opening_balance' => 500000.00,
            'is_archived' => false,
        ]);

        // Active wallet 2: 250,000 opening balance
        $wallet2 = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Dompet Tunai',
            'opening_balance' => 250000.00,
            'is_archived' => false,
        ]);

        // Archived wallet: 1,000,000 opening balance (must NOT be counted in active balance)
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Dompet Arsip',
            'opening_balance' => 1000000.00,
            'is_archived' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        // Total active balance is 500,000 + 250,000 = 750,000
        $response->assertSee('Rp750.000');
        $response->assertSee('BCA Utama');
        $response->assertSee('Dompet Tunai');
        $response->assertDontSee('Dompet Arsip');
    }

    /**
     * Dashboard calculates total income, total expense, and net cash flow accurately for current month.
     */
    public function test_dashboard_calculates_income_expense_and_net_cash_flow(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 0.00,
        ]);

        $catIncome = Category::factory()->income()->create(['user_id' => $this->user->id]);
        $catExpense = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $now = Carbon::now();

        // Income: 1,500,000 this month
        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catIncome->id,
            'amount' => 1500000.00,
            'transaction_date' => $now->toDateString(),
        ]);

        // Expense: 400,000 this month
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catExpense->id,
            'amount' => 400000.00,
            'transaction_date' => $now->toDateString(),
        ]);

        // Net cash flow = 1,500,000 - 400,000 = 1,100,000
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Rp1.500.000');
        $response->assertSee('Rp400.000');
        $response->assertSee('Rp1.100.000');
    }

    /**
     * Transfers must never be counted as income or expense on dashboard summary.
     */
    public function test_dashboard_excludes_transfers_from_income_and_expense_totals(): void
    {
        $sourceWallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 1000000.00,
        ]);
        $targetWallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 0.00,
        ]);

        // Transfer 300,000 between wallets
        Transaction::factory()->transfer()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $sourceWallet->id,
            'target_wallet_id' => $targetWallet->id,
            'category_id' => null,
            'amount' => 300000.00,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        // Income must be 0 and expense must be 0
        $response->assertViewHas('totalIncome', '0');
        $response->assertViewHas('totalExpense', '0');
        $response->assertViewHas('netCashFlow', '0.00');
    }

    /**
     * Dashboard supports period filtering: previous_month.
     */
    public function test_dashboard_supports_previous_month_filter(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $catIncome = Category::factory()->income()->create(['user_id' => $this->user->id]);

        $prevMonthDate = Carbon::now()->subMonth()->startOfMonth()->addDays(5)->toDateString();
        $currentMonthDate = Carbon::now()->startOfMonth()->addDays(2)->toDateString();

        // Income in previous month: 800,000
        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catIncome->id,
            'amount' => 800000.00,
            'transaction_date' => $prevMonthDate,
        ]);

        // Income in current month: 500,000
        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catIncome->id,
            'amount' => 500000.00,
            'transaction_date' => $currentMonthDate,
        ]);

        // Request with previous_month filter
        $response = $this->actingAs($this->user)->get(route('dashboard', ['period' => 'previous_month']));

        $response->assertOk();
        $response->assertViewHas('period', 'previous_month');
        $response->assertViewHas('totalIncome', '800000.00');
        $response->assertViewHas('startDate', Carbon::now()->subMonth()->startOfMonth()->toDateString());
        $response->assertViewHas('endDate', Carbon::now()->subMonth()->endOfMonth()->toDateString());
    }

    /**
     * Dashboard supports custom date range filter.
     */
    public function test_dashboard_supports_custom_date_range_filter(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $catExpense = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $startDate = '2026-05-01';
        $endDate = '2026-05-15';

        // Inside custom range: 175,000
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catExpense->id,
            'amount' => 175000.00,
            'transaction_date' => '2026-05-10',
        ]);

        // Outside custom range: 999,000
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catExpense->id,
            'amount' => 999000.00,
            'transaction_date' => '2026-05-20',
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard', [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertViewHas('period', 'custom');
        $response->assertViewHas('totalExpense', '175000.00');
        $response->assertViewHas('netCashFlow', '-175000.00');
        $response->assertViewHas('startDate', $startDate);
        $response->assertViewHas('endDate', $endDate);
        $response->assertSee('Rp175.000');
    }

    /**
     * Dashboard displays category expense breakdown.
     */
    public function test_dashboard_displays_expense_breakdown_by_category(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $foodCat = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Makanan & Minuman',
            'color' => '#f97316',
        ]);

        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $foodCat->id,
            'amount' => 250000.00,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Makanan &amp; Minuman', false);
        $response->assertSee('Rp250.000');
    }

    /**
     * Dashboard displays budget progress with warning and exceeded status.
     */
    public function test_dashboard_displays_budget_progress_with_thresholds(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Belanja Bulanan',
        ]);

        $now = Carbon::now();

        // Budget of 1,000,000 for current month
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat->id,
            'period_month' => $now->month,
            'period_year' => $now->year,
            'amount' => 1000000.00,
        ]);

        // Spent 850,000 (85% - triggers warning)
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'amount' => 850000.00,
            'transaction_date' => $now->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Belanja Bulanan');
        $response->assertSee('85%');
        $response->assertSee('Rp850.000');
        $response->assertSee('Rp1.000.000');
        $response->assertSee('Hampir penuh');
    }

    /**
     * Dashboard displays active savings goals and contributions.
     */
    public function test_dashboard_displays_savings_goals_progress(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Dana Darurat 2026',
            'target_amount' => 10000000.00,
            'status' => 'active',
        ]);

        // Contribution of 2,500,000 (25%)
        GoalContribution::factory()->create([
            'user_id' => $this->user->id,
            'savings_goal_id' => $goal->id,
            'wallet_id' => $wallet->id,
            'amount' => 2500000.00,
            'contribution_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dana Darurat 2026');
        $response->assertSee('25%');
        $response->assertSee('Rp2.500.000');
        $response->assertSee('Rp10.000.000');
    }

    /**
     * Dashboard displays recent transactions up to 8 items in descending order.
     */
    public function test_dashboard_displays_recent_transactions(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->income()->create(['user_id' => $this->user->id]);

        // Create 10 transactions with distinct letters to avoid substring collision
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($letters as $index => $letter) {
            Transaction::factory()->income()->create([
                'user_id' => $this->user->id,
                'wallet_id' => $wallet->id,
                'category_id' => $cat->id,
                'notes' => "TX-Item-{$letter}",
                'amount' => 10000.00 * ($index + 1),
                'transaction_date' => Carbon::now()->subDays(10 - $index)->toDateString(),
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        // Most recent 8 (C to J) should be visible, oldest 2 (A and B) should not be on recent list
        $response->assertSee('TX-Item-J');
        $response->assertSee('TX-Item-I');
        $response->assertSee('TX-Item-C');
        $response->assertDontSee('TX-Item-A');
        $response->assertDontSee('TX-Item-B');
    }

    /**
     * Strict multi-tenant isolation: User A must never see User B's financial data on dashboard.
     */
    public function test_dashboard_enforces_strict_multi_tenant_isolation(): void
    {
        $otherUser = User::factory()->create(['name' => 'Other User']);

        // Other user's wallet
        $otherWallet = Wallet::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Dompet Rahasia User Lain',
            'opening_balance' => 99000000.00,
        ]);

        // Other user's transaction
        $otherCat = Category::factory()->income()->create(['user_id' => $otherUser->id]);
        Transaction::factory()->income()->create([
            'user_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'category_id' => $otherCat->id,
            'notes' => 'Catatan Rahasia User B',
            'amount' => 50000000.00,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        // Other user's budget
        Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCat->id,
            'amount' => 77000000.00,
            'period_month' => Carbon::now()->month,
            'period_year' => Carbon::now()->year,
        ]);

        // Other user's goal
        SavingsGoal::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Target Mobil Mewah User B',
            'target_amount' => 500000000.00,
            'status' => 'active',
        ]);

        // User A visits dashboard
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        // User A should see none of User B's records
        $response->assertDontSee('Dompet Rahasia User Lain');
        $response->assertDontSee('Catatan Rahasia User B');
        $response->assertDontSee('Rp99.000.000');
        $response->assertDontSee('Rp50.000.000');
        $response->assertDontSee('Rp77.000.000');
        $response->assertDontSee('Target Mobil Mewah User B');
    }

    /**
     * Dashboard handles negative total balance gracefully with proper formatting.
     */
    public function test_dashboard_handles_negative_total_balance_gracefully(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Dompet Minus',
            'opening_balance' => -150000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('-Rp150.000');
    }

    /**
     * Dashboard handles completely empty state gracefully.
     */
    public function test_dashboard_handles_empty_state_gracefully(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Rp0');
        $response->assertSee('Belum ada transaksi.');
        $response->assertSee('Belum ada anggaran untuk periode ini.');
        $response->assertSee('Belum ada target tabungan aktif.');
    }
}
