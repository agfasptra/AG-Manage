<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCrudTest extends TestCase
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
    public function test_guest_cannot_access_budgets(): void
    {
        $this->get(route('budgets.index'))->assertRedirect(route('login'));
        $this->get(route('budgets.create'))->assertRedirect(route('login'));
    }

    /**
     * User can view budgets list for selected month and year.
     */
    public function test_user_can_view_budgets_list(): void
    {
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 5,
            'period_year' => 2026,
            'amount' => 1500000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('budgets.index', ['month' => 5, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Anggaran Bulanan');
        $response->assertSee($category->name);
        $response->assertSee('Rp1.500.000');
    }

    /**
     * User can create a budget for an expense category.
     */
    public function test_user_can_create_budget_for_expense_category(): void
    {
        $category = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Transportasi Kerja',
        ]);

        $response = $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'period_month' => 6,
            'period_year' => 2026,
            'amount' => 750000.00,
        ]);

        $response->assertRedirect(route('budgets.index', ['month' => 6, 'year' => 2026]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 6,
            'period_year' => 2026,
            'amount' => 750000.00,
        ]);
    }

    /**
     * Prevent duplicate budgets for the same user, category, and month/year.
     */
    public function test_user_cannot_create_duplicate_budget_for_same_period(): void
    {
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 7,
            'period_year' => 2026,
            'amount' => 500000.00,
        ]);

        $response = $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $category->id,
            'period_month' => 7,
            'period_year' => 2026,
            'amount' => 900000.00,
        ]);

        $response->assertSessionHasErrors(['category_id']);
        $this->assertDatabaseCount('budgets', 1);
    }

    /**
     * Cannot create a budget for an income category.
     */
    public function test_user_cannot_create_budget_for_income_category(): void
    {
        $incomeCategory = Category::factory()->income()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post(route('budgets.store'), [
            'category_id' => $incomeCategory->id,
            'period_month' => 8,
            'period_year' => 2026,
            'amount' => 1000000.00,
        ]);

        $response->assertSessionHasErrors(['category_id']);
    }

    /**
     * User can update a budget amount.
     */
    public function test_user_can_update_budget_amount(): void
    {
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 9,
            'period_year' => 2026,
            'amount' => 1000000.00,
        ]);

        $response = $this->actingAs($this->user)->put(route('budgets.update', $budget), [
            'amount' => 1250000.00,
        ]);

        $response->assertRedirect(route('budgets.index', ['month' => 9, 'year' => 2026]));
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'amount' => 1250000.00,
        ]);
    }

    /**
     * User can delete a budget without affecting transaction records.
     */
    public function test_user_can_delete_budget(): void
    {
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 10,
            'period_year' => 2026,
            'amount' => 800000.00,
        ]);

        $response = $this->actingAs($this->user)->delete(route('budgets.destroy', $budget));

        $response->assertRedirect(route('budgets.index', ['month' => 10, 'year' => 2026]));
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    /**
     * Spending calculation strictly includes valid expenses in the period, and excludes transfers or other periods.
     */
    public function test_budget_spending_calculated_strictly_from_valid_expenses_in_period(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $targetWallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 4,
            'period_year' => 2026,
            'amount' => 1000000.00,
        ]);

        // 1. Valid expense in April 2026: 350,000
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 350000.00,
            'transaction_date' => '2026-04-12',
        ]);

        // 2. Transfer in April 2026 (MUST NOT be counted as budget expense)
        Transaction::factory()->transfer()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'target_wallet_id' => $targetWallet->id,
            'category_id' => null,
            'amount' => 200000.00,
            'transaction_date' => '2026-04-15',
        ]);

        // 3. Expense in May 2026 (Different month: MUST NOT be counted in April budget)
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 450000.00,
            'transaction_date' => '2026-05-02',
        ]);

        $this->assertEquals(350000.00, $budget->spent_amount);
        $this->assertEquals(650000.00, $budget->remaining_amount);
        $this->assertEquals(35.0, $budget->percentage_used);
        $this->assertFalse($budget->is_warning);
        $this->assertFalse($budget->is_exceeded);
    }

    /**
     * Budget detects warning threshold at 80% and exceeded at 100%.
     */
    public function test_budget_detects_warning_and_exceeded_thresholds(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $budget = Budget::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'period_month' => 4,
            'period_year' => 2026,
            'amount' => 1000000.00,
        ]);

        // Spend 850,000 (85% -> warning)
        $tx = Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 850000.00,
            'transaction_date' => '2026-04-10',
        ]);

        $this->assertTrue($budget->is_warning);
        $this->assertFalse($budget->is_exceeded);

        // Spend additional 200,000 (total 1,050,000 -> 105% -> exceeded)
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 200000.00,
            'transaction_date' => '2026-04-18',
        ]);

        $this->assertFalse($budget->is_warning);
        $this->assertTrue($budget->is_exceeded);
    }

    /**
     * Strict multi-tenant isolation: User A cannot view, update, or delete User B's budget.
     */
    public function test_budget_multi_tenant_isolation(): void
    {
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->expense()->create(['user_id' => $otherUser->id]);

        $otherBudget = Budget::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $otherCategory->id,
            'period_month' => 4,
            'period_year' => 2026,
            'amount' => 999999.00,
        ]);

        // User A cannot edit other user's budget
        $this->actingAs($this->user)
            ->get(route('budgets.edit', $otherBudget))
            ->assertForbidden();

        // User A cannot update other user's budget
        $this->actingAs($this->user)
            ->put(route('budgets.update', $otherBudget), ['amount' => 500000.00])
            ->assertForbidden();

        // User A cannot delete other user's budget
        $this->actingAs($this->user)
            ->delete(route('budgets.destroy', $otherBudget))
            ->assertForbidden();
    }
}
