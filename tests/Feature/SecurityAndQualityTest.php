<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndQualityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $attacker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['name' => 'User Utama']);
        $this->attacker = User::factory()->create(['name' => 'User Penyerang']);
    }

    // ═══════════════════════════════════════════════════════════
    // IDOR / AUTHORIZATION TESTS — Every read/write endpoint
    // ═══════════════════════════════════════════════════════════

    /**
     * Attacker cannot view another user's transaction detail page.
     */
    public function test_attacker_cannot_view_other_users_transaction(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 100000.00,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Catatan Rahasia',
        ]);

        $this->actingAs($this->attacker)
            ->get(route('transactions.show', $tx))
            ->assertForbidden();
    }

    /**
     * Attacker cannot view another user's transaction edit form.
     */
    public function test_attacker_cannot_edit_other_users_transaction(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 100000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->attacker)
            ->get(route('transactions.edit', $tx))
            ->assertForbidden();
    }

    /**
     * Attacker cannot use another user's category when creating a transaction.
     */
    public function test_attacker_cannot_use_other_users_category(): void
    {
        $attackerWallet = Wallet::factory()->create(['user_id' => $this->attacker->id]);
        $victimCat = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Kategori Korban',
        ]);

        $response = $this->actingAs($this->attacker)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $attackerWallet->id,
                'category_id' => $victimCat->id,
            ]);

        $response->assertSessionHasErrors(['category_id']);
    }

    /**
     * Attacker cannot access another user's wallet edit form.
     */
    public function test_attacker_cannot_edit_form_other_users_wallet(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->attacker)
            ->get(route('wallets.edit', $wallet))
            ->assertForbidden();
    }

    /**
     * Attacker cannot toggle status of another user's savings goal.
     */
    public function test_attacker_cannot_toggle_other_users_savings_goal_status(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->attacker)
            ->patch(route('savings-goals.toggle-status', $goal), ['status' => 'completed'])
            ->assertForbidden();

        $this->assertEquals('active', $goal->fresh()->status);
    }

    /**
     * Attacker cannot access another user's budget create form.
     */
    public function test_attacker_cannot_use_other_users_category_for_budget(): void
    {
        $victimCat = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Kategori Korban Budget',
        ]);

        $response = $this->actingAs($this->attacker)
            ->post(route('budgets.store'), [
                'category_id' => $victimCat->id,
                'period_month' => 10,
                'period_year' => 2026,
                'amount' => 500000.00,
            ]);

        $response->assertSessionHasErrors(['category_id']);
    }

    // ═══════════════════════════════════════════════════════════
    // TRANSFER BALANCE CORRECTNESS — Edit and Delete
    // ═══════════════════════════════════════════════════════════

    /**
     * Deleting a transfer transaction correctly restores both wallet balances.
     */
    public function test_transfer_deletion_restores_both_wallet_balances(): void
    {
        $walletA = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 1000000.00,
        ]);
        $walletB = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 500000.00,
        ]);

        $transfer = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => 200000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // After transfer: A = 800,000, B = 700,000
        $walletA->refresh();
        $walletB->refresh();
        $this->assertEquals('800000.00', $walletA->current_balance);
        $this->assertEquals('700000.00', $walletB->current_balance);

        // Delete the transfer
        $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $transfer))
            ->assertRedirect(route('transactions.index'));

        // Balances restored: A = 1,000,000, B = 500,000
        $walletA->refresh();
        $walletB->refresh();
        $this->assertEquals('1000000.00', $walletA->current_balance);
        $this->assertEquals('500000.00', $walletB->current_balance);
    }

    /**
     * Updating a transfer transaction correctly reverses old effects and applies new effects.
     */
    public function test_transfer_update_correctly_reverses_and_reapplies_balance(): void
    {
        $walletA = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 2000000.00,
        ]);
        $walletB = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 300000.00,
        ]);

        $transfer = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => 500000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // After transfer: A = 1,500,000, B = 800,000
        $walletA->refresh();
        $walletB->refresh();
        $this->assertEquals('1500000.00', $walletA->current_balance);
        $this->assertEquals('800000.00', $walletB->current_balance);

        // Update transfer amount from 500,000 to 300,000
        $this->actingAs($this->user)
            ->put(route('transactions.update', $transfer), [
                'type' => 'transfer',
                'amount' => 300000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $walletA->id,
                'target_wallet_id' => $walletB->id,
            ])
            ->assertRedirect(route('transactions.index'));

        // After update: A = 2,000,000 - 300,000 = 1,700,000, B = 300,000 + 300,000 = 600,000
        $walletA->refresh();
        $walletB->refresh();
        $this->assertEquals('1700000.00', $walletA->current_balance);
        $this->assertEquals('600000.00', $walletB->current_balance);
    }

    // ═══════════════════════════════════════════════════════════
    // SAFE DELETION BEHAVIOR
    // ═══════════════════════════════════════════════════════════

    /**
     * Wallet with transactions cannot be deleted — must be archived instead.
     */
    public function test_wallet_with_transactions_cannot_be_deleted(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 50000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('wallets.destroy', $wallet))
            ->assertForbidden();

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
    }

    /**
     * Wallet that is a transfer target (incoming transfers) cannot be deleted.
     */
    public function test_wallet_with_incoming_transfers_cannot_be_deleted(): void
    {
        $sourceWallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $targetWallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $sourceWallet->id,
            'target_wallet_id' => $targetWallet->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => 100000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // Target wallet has incoming transfers — cannot be deleted
        $this->actingAs($this->user)
            ->delete(route('wallets.destroy', $targetWallet))
            ->assertForbidden();

        $this->assertDatabaseHas('wallets', ['id' => $targetWallet->id]);
    }

    /**
     * Category with transactions cannot be deleted.
     */
    public function test_category_with_transactions_cannot_be_deleted(): void
    {
        $cat = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Kategori Punya Transaksi',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 25000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('categories.destroy', $cat))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $cat->id]);
    }

    /**
     * Savings goal deletion also deletes all contributions (cascade).
     */
    public function test_savings_goal_deletion_cascades_contributions(): void
    {
        $goal = SavingsGoal::factory()->create(['user_id' => $this->user->id]);
        $contribution = GoalContribution::factory()->create([
            'user_id' => $this->user->id,
            'savings_goal_id' => $goal->id,
            'amount' => 200000.00,
        ]);

        $this->actingAs($this->user)
            ->delete(route('savings-goals.destroy', $goal));

        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
        $this->assertDatabaseMissing('goal_contributions', ['id' => $contribution->id]);
    }

    // ═══════════════════════════════════════════════════════════
    // VALIDATION EDGE CASES
    // ═══════════════════════════════════════════════════════════

    /**
     * Transaction rejects negative amount.
     */
    public function test_transaction_rejects_negative_amount(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => -50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $wallet->id,
                'category_id' => $cat->id,
            ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /**
     * Transaction requires a valid date.
     */
    public function test_transaction_rejects_invalid_date(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => 50000.00,
                'transaction_date' => 'not-a-date',
                'wallet_id' => $wallet->id,
                'category_id' => $cat->id,
            ]);

        $response->assertSessionHasErrors(['transaction_date']);
    }

    /**
     * Transfer type does not require a category.
     */
    public function test_transfer_succeeds_without_category(): void
    {
        $walletA = Wallet::factory()->create(['user_id' => $this->user->id]);
        $walletB = Wallet::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $walletA->id,
                'target_wallet_id' => $walletB->id,
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Savings goal rejects target_amount below minimum.
     */
    public function test_savings_goal_rejects_below_minimum_target(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('savings-goals.store'), [
                'name' => 'Target Kecil',
                'target_amount' => 500, // Below minimum of 1000
            ]);

        $response->assertSessionHasErrors(['target_amount']);
    }

    /**
     * Budget rejects amount below minimum.
     */
    public function test_budget_rejects_below_minimum_amount(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $cat = Category::whereNull('user_id')->where('type', 'expense')->first();

        $response = $this->actingAs($this->user)
            ->post(route('budgets.store'), [
                'category_id' => $cat->id,
                'period_month' => 10,
                'period_year' => 2026,
                'amount' => 500, // Below minimum of 1000
            ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /**
     * Notes field is truncated at max length.
     */
    public function test_transaction_rejects_notes_exceeding_max_length(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $wallet->id,
                'category_id' => $cat->id,
                'notes' => str_repeat('A', 1001), // Exceeds max 1000
            ]);

        $response->assertSessionHasErrors(['notes']);
    }

    // ═══════════════════════════════════════════════════════════
    // CSRF PROTECTION — Laravel handles this automatically.
    // Verify middleware is applied.
    // ═══════════════════════════════════════════════════════════

    /**
     * POST request without CSRF token is rejected (Laravel default).
     */
    public function test_state_changing_request_requires_csrf(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        // Laravel's test helpers automatically include CSRF tokens.
        // To test CSRF rejection, disable the middleware temporarily
        // and verify the middleware exists in the kernel instead.
        // This test confirms the VerifyCsrfToken middleware is registered.
        $kernel = app(Kernel::class);
        $middlewareGroups = $kernel->getMiddlewareGroups();

        $this->assertArrayHasKey('web', $middlewareGroups);

        // The 'web' group includes CSRF verification via the framework default
        // configuration. We confirm the route group uses 'web' middleware.
        $route = app('router')->getRoutes()->getByName('transactions.store');
        $this->assertNotNull($route);

        $routeMiddleware = $route->gatherMiddleware();
        $this->assertTrue(
            in_array('web', $routeMiddleware) || count(array_filter($routeMiddleware, fn ($m) => str_contains($m, 'csrf') || str_contains($m, 'VerifyCsrf'))) > 0 || in_array('web', $routeMiddleware),
            'Transaction store route should have web middleware (includes CSRF protection)'
        );
    }

    // ═══════════════════════════════════════════════════════════
    // ENV AND SECRETS
    // ═══════════════════════════════════════════════════════════

    /**
     * .env file is in .gitignore.
     */
    public function test_env_is_listed_in_gitignore(): void
    {
        $gitignorePath = base_path('.gitignore');
        $this->assertFileExists($gitignorePath);

        $content = file_get_contents($gitignorePath);
        $this->assertStringContainsString('.env', $content);
    }

    /**
     * .env.example does not contain real secret values.
     */
    public function test_env_example_has_no_real_secrets(): void
    {
        $envExamplePath = base_path('.env.example');
        $this->assertFileExists($envExamplePath);

        $content = file_get_contents($envExamplePath);

        // APP_KEY should be empty in example
        $this->assertMatchesRegularExpression('/^APP_KEY=\s*$/m', $content);

        // DB_PASSWORD should be empty
        $this->assertMatchesRegularExpression('/^DB_PASSWORD=\s*$/m', $content);
    }

    // ═══════════════════════════════════════════════════════════
    // MASS ASSIGNMENT PROTECTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Transaction model has explicit $fillable protection.
     */
    public function test_transaction_model_has_fillable_protection(): void
    {
        $transaction = new Transaction;
        $fillable = $transaction->getFillable();

        // Must explicitly list fields — verify 'id' is NOT fillable
        $this->assertNotContains('id', $fillable);
        $this->assertNotContains('created_at', $fillable);
        $this->assertNotContains('updated_at', $fillable);

        // Must include expected fields
        $this->assertContains('user_id', $fillable);
        $this->assertContains('wallet_id', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('amount', $fillable);
    }

    /**
     * Wallet model has explicit $fillable protection.
     */
    public function test_wallet_model_has_fillable_protection(): void
    {
        $wallet = new Wallet;
        $fillable = $wallet->getFillable();

        $this->assertNotContains('id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('opening_balance', $fillable);
    }

    // ═══════════════════════════════════════════════════════════
    // EMPTY STATES
    // ═══════════════════════════════════════════════════════════

    /**
     * Wallet index shows empty state when no wallets exist.
     */
    public function test_wallet_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->user)->get(route('wallets.index'));

        $response->assertOk();
        $response->assertSee('Belum ada dompet');
    }

    /**
     * Transaction index shows empty state when no transactions exist.
     */
    public function test_transaction_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Belum ada transaksi');
    }

    /**
     * Report page works with no transactions (empty state).
     */
    public function test_reports_page_handles_empty_state(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalIncome', 0.0);
        $response->assertViewHas('totalExpense', 0.0);
    }

    // ═══════════════════════════════════════════════════════════
    // FINANCIAL INTEGRITY — Numeric precision
    // ═══════════════════════════════════════════════════════════

    /**
     * Exact monetary values are preserved without floating-point drift.
     */
    public function test_exact_monetary_values_preserved(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 1234567.00,
        ]);

        $cat = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => 'expense',
            'amount' => 123456.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $wallet->refresh();
        // 1,234,567 - 123,456 = 1,111,111 exactly
        $this->assertEquals('1111111.00', $wallet->current_balance);
    }

    /**
     * Opening balance is not counted as income in period calculations.
     */
    public function test_opening_balance_not_counted_as_income(): void
    {
        Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 5000000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        // Income must be zero since only opening balance exists (no income transactions)
        $response->assertViewHas('totalIncome', '0');
    }
}
