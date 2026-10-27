<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Wallet $walletA;

    private Wallet $walletB;

    private Category $incomeCat;

    private Category $expenseCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->walletA = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'BCA Utama',
            'opening_balance' => 1000000.00,
        ]);

        $this->walletB = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'GoPay',
            'opening_balance' => 200000.00,
        ]);

        $this->incomeCat = Category::factory()->income()->create([
            'user_id' => $this->user->id,
            'name' => 'Gaji Bulanan',
        ]);

        $this->expenseCat = Category::factory()->expense()->create([
            'user_id' => $this->user->id,
            'name' => 'Makanan',
        ]);
    }

    // ─── AUTHENTICATION & ACCESS ──────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
        $this->get(route('transactions.create'))->assertRedirect(route('login'));
        $this->post(route('transactions.store'), [])->assertRedirect(route('login'));
    }

    public function test_user_can_view_transactions_index(): void
    {
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->expenseCat->id,
            'notes' => 'Beli nasi padang',
        ]);

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Beli nasi padang')
            ->assertSee('Makanan');
    }

    public function test_user_cannot_see_other_users_transactions(): void
    {
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);
        $otherCat = Category::factory()->expense()->create(['user_id' => $otherUser->id]);

        $otherTx = Transaction::factory()->expense()->create([
            'user_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'category_id' => $otherCat->id,
            'notes' => 'Catatan Rahasia User Lain',
        ]);

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertDontSee('Catatan Rahasia User Lain');
    }

    // ─── INCOME CREATION & BALANCE ────────────────────────────

    public function test_user_can_create_income_transaction(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'income',
                'amount' => 500000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'category_id' => $this->incomeCat->id,
                'notes' => 'Bonus project',
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Pemasukan berhasil dicatat.');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'type' => 'income',
            'amount' => 500000.00,
            'notes' => 'Bonus project',
        ]);

        // Opening balance 1,000,000 + 500,000 income = 1,500,000
        $this->walletA->refresh();
        $this->assertEquals('1500000.00', $this->walletA->current_balance);
        $this->assertEquals('Rp1.500.000', $this->walletA->formatted_balance);
    }

    // ─── EXPENSE CREATION & BALANCE ───────────────────────────

    public function test_user_can_create_expense_transaction(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => 150000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'category_id' => $this->expenseCat->id,
                'notes' => 'Belanja mingguan',
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Pengeluaran berhasil dicatat.');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'type' => 'expense',
            'amount' => 150000.00,
        ]);

        // Opening balance 1,000,000 - 150,000 = 850,000
        $this->walletA->refresh();
        $this->assertEquals('850000.00', $this->walletA->current_balance);
    }

    // ─── TRANSFER & BALANCE ───────────────────────────────────

    public function test_user_can_create_transfer_between_two_wallets(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'amount' => 100000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'target_wallet_id' => $this->walletB->id,
                'notes' => 'Topup GoPay dari BCA',
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transfer antar-dompet berhasil dicatat.');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'target_wallet_id' => $this->walletB->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => 100000.00,
        ]);

        // Wallet A: 1,000,000 - 100,000 = 900,000
        $this->walletA->refresh();
        $this->assertEquals('900000.00', $this->walletA->current_balance);

        // Wallet B: 200,000 + 100,000 = 300,000
        $this->walletB->refresh();
        $this->assertEquals('300000.00', $this->walletB->current_balance);
    }

    public function test_transfer_requires_different_source_and_target_wallets(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'target_wallet_id' => $this->walletA->id, // Same wallet!
            ]);

        $response->assertSessionHasErrors(['target_wallet_id']);
    }

    public function test_transfer_cannot_target_other_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'transfer',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'target_wallet_id' => $otherWallet->id,
            ]);

        $response->assertSessionHasErrors(['target_wallet_id']);
    }

    // ─── VALIDATION RULES ─────────────────────────────────────

    public function test_transaction_requires_positive_amount(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => 0,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'category_id' => $this->expenseCat->id,
            ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_income_cannot_use_expense_category(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'income',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'category_id' => $this->expenseCat->id, // Expense category for income!
            ]);

        $response->assertSessionHasErrors(['category_id']);
    }

    public function test_user_cannot_use_other_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->post(route('transactions.store'), [
                'type' => 'expense',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $otherWallet->id,
                'category_id' => $this->expenseCat->id,
            ]);

        $response->assertSessionHasErrors(['wallet_id']);
    }

    // ─── EDIT & UPDATE ────────────────────────────────────────

    public function test_user_can_update_transaction_reversing_old_effects(): void
    {
        // Initial expense: 200,000 on Wallet A
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->expenseCat->id,
            'type' => 'expense',
            'amount' => 200000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->walletA->refresh();
        $this->assertEquals('800000.00', $this->walletA->current_balance);

        // Update amount from 200,000 to 50,000
        $response = $this->actingAs($this->user)
            ->put(route('transactions.update', $tx), [
                'type' => 'expense',
                'amount' => 50000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'category_id' => $this->expenseCat->id,
                'notes' => 'Diubah jadi 50rb',
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transaksi berhasil diperbarui.');

        // Balance must adjust accurately: 1,000,000 - 50,000 = 950,000
        $this->walletA->refresh();
        $this->assertEquals('950000.00', $this->walletA->current_balance);
    }

    public function test_user_cannot_update_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);
        $otherCat = Category::factory()->expense()->create(['user_id' => $otherUser->id]);

        $otherTx = Transaction::create([
            'user_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'category_id' => $otherCat->id,
            'type' => 'expense',
            'amount' => 100000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->put(route('transactions.update', $otherTx), [
                'type' => 'expense',
                'amount' => 10000.00,
                'transaction_date' => now()->toDateString(),
                'wallet_id' => $this->walletA->id,
                'category_id' => $this->expenseCat->id,
            ])
            ->assertForbidden();
    }

    // ─── DELETE ───────────────────────────────────────────────

    public function test_user_can_delete_transaction_restoring_balance(): void
    {
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->expenseCat->id,
            'type' => 'expense',
            'amount' => 300000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->walletA->refresh();
        $this->assertEquals('700000.00', $this->walletA->current_balance);

        $response = $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $tx));

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', 'Transaksi berhasil dihapus.');

        $this->assertDatabaseMissing('transactions', ['id' => $tx->id]);

        // Balance restored to 1,000,000
        $this->walletA->refresh();
        $this->assertEquals('1000000.00', $this->walletA->current_balance);
    }

    public function test_user_cannot_delete_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id]);
        $otherCat = Category::factory()->expense()->create(['user_id' => $otherUser->id]);

        $otherTx = Transaction::create([
            'user_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'category_id' => $otherCat->id,
            'type' => 'expense',
            'amount' => 50000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('transactions.destroy', $otherTx))
            ->assertForbidden();

        $this->assertDatabaseHas('transactions', ['id' => $otherTx->id]);
    }

    // ─── FINANCIAL CALCULATIONS & REPORT RULES ────────────────

    public function test_transfers_are_excluded_from_income_and_expense_totals(): void
    {
        // 1 Income of 500k
        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->incomeCat->id,
            'type' => 'income',
            'amount' => 500000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // 1 Expense of 200k
        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->expenseCat->id,
            'type' => 'expense',
            'amount' => 200000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        // 1 Transfer of 150k between Wallet A and Wallet B
        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'target_wallet_id' => $this->walletB->id,
            'category_id' => null,
            'type' => 'transfer',
            'amount' => 150000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('transactions.index'));
        $response->assertOk();

        // Income should be 500,000, Expense should be 200,000, Net should be 300,000
        $response->assertViewHas('totalIncome', 500000.00);
        $response->assertViewHas('totalExpense', 200000.00);
        $response->assertViewHas('netCashFlow', '300000.00');
        $response->assertViewHas('totalTransfer', 150000.00);
    }

    // ─── NEGATIVE BALANCE ─────────────────────────────────────

    public function test_negative_balance_is_supported_and_formatted(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 50000.00,
        ]);

        // Expense of 120,000 from wallet with 50,000 balance
        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $this->expenseCat->id,
            'type' => 'expense',
            'amount' => 120000.00,
            'transaction_date' => now()->toDateString(),
        ]);

        $wallet->refresh();
        $this->assertEquals('-70000.00', $wallet->current_balance);
        $this->assertEquals('-Rp70.000', $wallet->formatted_balance);
    }

    // ─── FILTERING ────────────────────────────────────────────

    public function test_transactions_can_be_filtered_by_type(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->incomeCat->id,
            'type' => 'income',
            'amount' => 100000.00,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Pemasukan A',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->walletA->id,
            'category_id' => $this->expenseCat->id,
            'type' => 'expense',
            'amount' => 50000.00,
            'transaction_date' => now()->toDateString(),
            'notes' => 'Pengeluaran B',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('transactions.index', ['type' => 'income']));

        $response->assertOk()
            ->assertSee('Pemasukan A')
            ->assertDontSee('Pengeluaran B');
    }
}
