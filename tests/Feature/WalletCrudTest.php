<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── INDEX ──────────────────────────────────────────

    public function test_guest_cannot_access_wallets(): void
    {
        $this->get(route('wallets.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_see_wallet_list(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('wallets.index'))
            ->assertOk()
            ->assertSee($wallet->name);
    }

    public function test_user_sees_only_own_wallets(): void
    {
        $otherUser = User::factory()->create();
        $ownWallet = Wallet::factory()->create(['user_id' => $this->user->id, 'name' => 'My Wallet']);
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id, 'name' => 'Other Wallet']);

        $this->actingAs($this->user)
            ->get(route('wallets.index'))
            ->assertSee('My Wallet')
            ->assertDontSee('Other Wallet');
    }

    // ─── CREATE / STORE ─────────────────────────────────

    public function test_user_can_view_create_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('wallets.create'))
            ->assertOk()
            ->assertSee('Tambah Dompet');
    }

    public function test_user_can_create_wallet(): void
    {
        $this->actingAs($this->user)
            ->post(route('wallets.store'), [
                'name' => 'BCA',
                'type' => 'bank',
                'opening_balance' => 1500000,
                'description' => 'Rekening utama',
            ])
            ->assertRedirect(route('wallets.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'name' => 'BCA',
            'type' => 'bank',
            'opening_balance' => '1500000.00',
        ]);
    }

    public function test_wallet_validation_rejects_missing_name(): void
    {
        $this->actingAs($this->user)
            ->post(route('wallets.store'), [
                'name' => '',
                'type' => 'cash',
                'opening_balance' => 0,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_wallet_validation_rejects_invalid_type(): void
    {
        $this->actingAs($this->user)
            ->post(route('wallets.store'), [
                'name' => 'Test',
                'type' => 'crypto',
                'opening_balance' => 0,
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_wallet_validation_rejects_negative_balance(): void
    {
        $this->actingAs($this->user)
            ->post(route('wallets.store'), [
                'name' => 'Test',
                'type' => 'cash',
                'opening_balance' => -50000,
            ])
            ->assertSessionHasErrors('opening_balance');
    }

    // ─── SHOW ───────────────────────────────────────────

    public function test_user_can_view_own_wallet(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('wallets.show', $wallet))
            ->assertOk()
            ->assertSee($wallet->name);
    }

    public function test_user_cannot_view_other_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->get(route('wallets.show', $wallet))
            ->assertForbidden();
    }

    // ─── EDIT / UPDATE ──────────────────────────────────

    public function test_user_can_edit_own_wallet(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('wallets.edit', $wallet))
            ->assertOk();
    }

    public function test_user_can_update_own_wallet(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $this->actingAs($this->user)
            ->put(route('wallets.update', $wallet), [
                'name' => 'New Name',
                'type' => 'ewallet',
                'opening_balance' => 500000,
            ])
            ->assertRedirect(route('wallets.show', $wallet))
            ->assertSessionHas('success');

        $wallet->refresh();
        $this->assertEquals('New Name', $wallet->name);
        $this->assertEquals('ewallet', $wallet->type);
    }

    public function test_user_cannot_update_other_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->put(route('wallets.update', $wallet), [
                'name' => 'Hacked',
                'type' => 'cash',
                'opening_balance' => 0,
            ])
            ->assertForbidden();
    }

    // ─── ARCHIVE / RESTORE ──────────────────────────────

    public function test_user_can_archive_own_wallet(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'is_archived' => false,
        ]);

        $this->actingAs($this->user)
            ->patch(route('wallets.toggle-archive', $wallet))
            ->assertRedirect(route('wallets.index'));

        $wallet->refresh();
        $this->assertTrue($wallet->is_archived);
    }

    public function test_user_can_restore_archived_wallet(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'is_archived' => true,
        ]);

        $this->actingAs($this->user)
            ->patch(route('wallets.toggle-archive', $wallet))
            ->assertRedirect(route('wallets.index'));

        $wallet->refresh();
        $this->assertFalse($wallet->is_archived);
    }

    public function test_user_cannot_archive_other_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->patch(route('wallets.toggle-archive', $wallet))
            ->assertForbidden();
    }

    // ─── DELETE ─────────────────────────────────────────

    public function test_user_can_delete_wallet_without_transactions(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('wallets.destroy', $wallet))
            ->assertRedirect(route('wallets.index'));

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
    }

    public function test_user_cannot_delete_other_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user)
            ->delete(route('wallets.destroy', $wallet))
            ->assertForbidden();
    }

    // ─── BALANCE CALCULATION ────────────────────────────

    public function test_wallet_balance_equals_opening_balance_when_no_transactions(): void
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'opening_balance' => 2500000,
        ]);

        $this->assertEquals('2500000.00', $wallet->current_balance);
        $this->assertEquals('Rp2.500.000', $wallet->formatted_balance);
    }

    public function test_wallet_type_label_returns_indonesian(): void
    {
        $cash = Wallet::factory()->create(['user_id' => $this->user->id, 'type' => 'cash']);
        $bank = Wallet::factory()->create(['user_id' => $this->user->id, 'type' => 'bank']);
        $ewallet = Wallet::factory()->create(['user_id' => $this->user->id, 'type' => 'ewallet']);

        $this->assertEquals('Tunai', $cash->type_label);
        $this->assertEquals('Bank', $bank->type_label);
        $this->assertEquals('E-Wallet', $ewallet->type_label);
    }

    public function test_wallet_scopes_filter_correctly(): void
    {
        $active = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'is_archived' => false,
        ]);
        $archived = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'is_archived' => true,
        ]);

        $activeWallets = $this->user->wallets()->active()->get();
        $archivedWallets = $this->user->wallets()->archived()->get();

        $this->assertCount(1, $activeWallets);
        $this->assertCount(1, $archivedWallets);
        $this->assertEquals($active->id, $activeWallets->first()->id);
        $this->assertEquals($archived->id, $archivedWallets->first()->id);
    }
}
