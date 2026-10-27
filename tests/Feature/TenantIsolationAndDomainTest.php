<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationAndDomainTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated guests are redirected to login when accessing protected routes.
     */
    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test authenticated user can access dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('Isolasi Multi-Tenancy');
    }

    /**
     * Test full domain models, relationships, and user data isolation.
     */
    public function test_user_data_isolation_across_tenants(): void
    {
        // 1. Create two separate users
        $userA = User::factory()->create(['name' => 'User A', 'email' => 'usera@example.com']);
        $userB = User::factory()->create(['name' => 'User B', 'email' => 'userb@example.com']);

        // 2. Create wallets for both users
        $walletA = Wallet::create([
            'user_id' => $userA->id,
            'name' => 'BCA User A',
            'type' => 'bank',
            'opening_balance' => 1000000.00,
            'currency' => 'IDR',
        ]);

        $walletB = Wallet::create([
            'user_id' => $userB->id,
            'name' => 'Mandiri User B',
            'type' => 'bank',
            'opening_balance' => 2000000.00,
            'currency' => 'IDR',
        ]);

        // 3. Create categories
        $catA = Category::create([
            'user_id' => $userA->id,
            'name' => 'Kategori Khusus A',
            'type' => 'expense',
        ]);

        $catB = Category::create([
            'user_id' => $userB->id,
            'name' => 'Kategori Khusus B',
            'type' => 'expense',
        ]);

        // 4. Create transactions
        $txA = Transaction::create([
            'user_id' => $userA->id,
            'wallet_id' => $walletA->id,
            'category_id' => $catA->id,
            'type' => 'expense',
            'amount' => 50000.00,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Makan Siang A',
        ]);

        $txB = Transaction::create([
            'user_id' => $userB->id,
            'wallet_id' => $walletB->id,
            'category_id' => $catB->id,
            'type' => 'expense',
            'amount' => 75000.00,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Makan Siang B',
        ]);

        // 5. Create budgets
        $budgetA = Budget::create([
            'user_id' => $userA->id,
            'category_id' => $catA->id,
            'period_month' => 10,
            'period_year' => 2026,
            'amount' => 500000.00,
        ]);

        $budgetB = Budget::create([
            'user_id' => $userB->id,
            'category_id' => $catB->id,
            'period_month' => 10,
            'period_year' => 2026,
            'amount' => 750000.00,
        ]);

        // 6. Create savings goals and contributions
        $goalA = SavingsGoal::create([
            'user_id' => $userA->id,
            'name' => 'Beli Laptop A',
            'target_amount' => 15000000.00,
        ]);

        $goalB = SavingsGoal::create([
            'user_id' => $userB->id,
            'name' => 'Liburan B',
            'target_amount' => 5000000.00,
        ]);

        $contribA = GoalContribution::create([
            'user_id' => $userA->id,
            'savings_goal_id' => $goalA->id,
            'wallet_id' => $walletA->id,
            'amount' => 500000.00,
            'contribution_date' => now()->toDateString(),
        ]);

        // 7. Assert isolation on userA relationships
        $this->assertCount(1, $userA->wallets);
        $this->assertEquals($walletA->id, $userA->wallets->first()->id);
        $this->assertFalse($userA->wallets->contains($walletB));

        $this->assertCount(1, $userA->categories);
        $this->assertEquals($catA->id, $userA->categories->first()->id);
        $this->assertFalse($userA->categories->contains($catB));

        $this->assertCount(1, $userA->transactions);
        $this->assertEquals($txA->id, $userA->transactions->first()->id);
        $this->assertFalse($userA->transactions->contains($txB));

        $this->assertCount(1, $userA->budgets);
        $this->assertEquals($budgetA->id, $userA->budgets->first()->id);
        $this->assertFalse($userA->budgets->contains($budgetB));

        $this->assertCount(1, $userA->savingsGoals);
        $this->assertEquals($goalA->id, $userA->savingsGoals->first()->id);
        $this->assertFalse($userA->savingsGoals->contains($goalB));

        $this->assertCount(1, $userA->goalContributions);
        $this->assertEquals($contribA->id, $userA->goalContributions->first()->id);

        // 8. Assert isolation on userB relationships
        $this->assertCount(1, $userB->wallets);
        $this->assertEquals($walletB->id, $userB->wallets->first()->id);
        $this->assertFalse($userB->wallets->contains($walletA));

        $this->assertCount(1, $userB->categories);
        $this->assertEquals($catB->id, $userB->categories->first()->id);

        $this->assertCount(1, $userB->transactions);
        $this->assertEquals($txB->id, $userB->transactions->first()->id);

        $this->assertCount(1, $userB->budgets);
        $this->assertEquals($budgetB->id, $userB->budgets->first()->id);

        $this->assertCount(1, $userB->savingsGoals);
        $this->assertEquals($goalB->id, $userB->savingsGoals->first()->id);

        // 9. Assert model inverse relationships
        $this->assertEquals($userA->id, $walletA->user->id);
        $this->assertEquals($userA->id, $catA->user->id);
        $this->assertEquals($userA->id, $txA->user->id);
        $this->assertEquals($walletA->id, $txA->wallet->id);
        $this->assertEquals($catA->id, $txA->category->id);
        $this->assertEquals($userA->id, $budgetA->user->id);
        $this->assertEquals($catA->id, $budgetA->category->id);
        $this->assertEquals($userA->id, $goalA->user->id);
        $this->assertEquals($goalA->id, $contribA->savingsGoal->id);
        $this->assertEquals($walletA->id, $contribA->wallet->id);
    }

    /**
     * Test transfer transaction supports source and destination wallets.
     */
    public function test_transfer_transaction_supports_two_wallets(): void
    {
        $user = User::factory()->create();

        $sourceWallet = Wallet::create([
            'user_id' => $user->id,
            'name' => 'BCA Rekening',
            'type' => 'bank',
            'opening_balance' => 5000000.00,
        ]);

        $targetWallet = Wallet::create([
            'user_id' => $user->id,
            'name' => 'GoPay',
            'type' => 'ewallet',
            'opening_balance' => 100000.00,
        ]);

        $transfer = Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $sourceWallet->id,
            'target_wallet_id' => $targetWallet->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => 250000.00,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Topup GoPay',
        ]);

        $this->assertEquals('transfer', $transfer->type);
        $this->assertEquals($sourceWallet->id, $transfer->wallet->id);
        $this->assertEquals($targetWallet->id, $transfer->targetWallet->id);
        $this->assertNull($transfer->category_id);
    }
}
