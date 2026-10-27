<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\InsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightAndSafeToSpendTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private InsightService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = app(InsightService::class);
    }

    /**
     * Safe-to-spend calculation: total active wallet balance minus unspent budget commitments.
     */
    public function test_safe_to_spend_calculation(): void
    {
        // 1. Two active wallets with total balance = 5,000,000
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 3000000.00,
            'is_archived' => false,
        ]);
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 2000000.00,
            'is_archived' => false,
        ]);

        // Archived wallet with 10,000,000 (must be ignored)
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 10000000.00,
            'is_archived' => true,
        ]);

        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        // 2. Budget of 1,500,000 for May 2026
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat->id,
            'period_month' => 5,
            'period_year' => 2026,
            'amount' => 1500000.00,
        ]);

        // Spent 500,000 this month -> unspent budget commitment is 1,000,000
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat->id,
            'amount' => 500000.00,
            'transaction_date' => '2026-05-10',
        ]);

        // Safe-to-spend = 5,000,000 (active balance) - 1,000,000 (unspent budget commitment) = 4,000,000
        $result = $this->service->calculateSafeToSpend($this->user, 5, 2026);

        $this->assertEquals(5000000.00, $result['total_balance']);
        $this->assertEquals(1000000.00, $result['budget_commitments']);
        $this->assertEquals(4000000.00, $result['safe_to_spend']);
        $this->assertEquals('Rp4.000.000', $result['formatted_safe_to_spend']);
        $this->assertNotEmpty($result['explanation']);
    }

    /**
     * Rule-based insights correctly generate alerts for budget warning and exceeded states.
     */
    public function test_rule_based_insights_for_budgets(): void
    {
        $cat1 = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Makan & Minum',
        ]);
        $cat2 = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Belanja Online',
        ]);

        // Budget 1: 1,000,000 with 850,000 spent (85% -> warning)
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat1->id,
            'period_month' => 5,
            'period_year' => 2026,
            'amount' => 1000000.00,
        ]);
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat1->id,
            'amount' => 850000.00,
            'transaction_date' => '2026-05-15',
        ]);

        // Budget 2: 500,000 with 600,000 spent (120% -> exceeded)
        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat2->id,
            'period_month' => 5,
            'period_year' => 2026,
            'amount' => 500000.00,
        ]);
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat2->id,
            'amount' => 600000.00,
            'transaction_date' => '2026-05-18',
        ]);

        $insights = $this->service->getMonthlyInsights($this->user, 5, 2026);

        $types = array_column($insights, 'type');
        $this->assertContains('budget_warning', $types);
        $this->assertContains('budget_exceeded', $types);
    }

    /**
     * Rule-based insights detect category spending increase/decrease compared to previous month.
     */
    public function test_rule_based_insights_month_over_month_comparison(): void
    {
        $cat = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Bensin & Tol',
        ]);

        // Previous month (April 2026): 200,000
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat->id,
            'amount' => 200000.00,
            'transaction_date' => '2026-04-15',
        ]);

        // Current month (May 2026): 350,000 (+75% increase -> triggers spending_increase alert)
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'category_id' => $cat->id,
            'amount' => 350000.00,
            'transaction_date' => '2026-05-15',
        ]);

        $insights = $this->service->getMonthlyInsights($this->user, 5, 2026);

        $types = array_column($insights, 'type');
        $this->assertContains('spending_increase', $types);
    }

    /**
     * Rule-based insights detect savings goal milestones.
     */
    public function test_rule_based_insights_savings_goal_milestone(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Ganti Handphone',
            'target_amount' => 4000000.00,
            'status' => 'active',
        ]);

        // 3,200,000 saved (80% milestone)
        GoalContribution::factory()->create([
            'user_id' => $this->user->id,
            'savings_goal_id' => $goal->id,
            'amount' => 3200000.00,
        ]);

        $insights = $this->service->getMonthlyInsights($this->user, 5, 2026);

        $types = array_column($insights, 'type');
        $this->assertContains('goal_milestone', $types);
    }
}
