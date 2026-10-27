<?php

namespace Tests\Feature;

use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsGoalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Guest cannot access savings goals endpoints.
     */
    public function test_guest_cannot_access_savings_goals(): void
    {
        $this->get(route('savings-goals.index'))->assertRedirect(route('login'));
        $this->get(route('savings-goals.create'))->assertRedirect(route('login'));
    }

    /**
     * User can view savings goals listing.
     */
    public function test_user_can_view_savings_goals_list(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beli Laptop Baru',
            'target_amount' => 15000000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('savings-goals.index'));

        $response->assertOk();
        $response->assertSee('Target Tabungan');
        $response->assertSee('Beli Laptop Baru');
        $response->assertSee('Rp15.000.000');
    }

    /**
     * User can create a new savings goal.
     */
    public function test_user_can_create_savings_goal(): void
    {
        $response = $this->actingAs($this->user)->post(route('savings-goals.store'), [
            'name' => 'Dana Liburan Jepang',
            'target_amount' => 25000000.00,
            'target_date' => Carbon::now()->addMonths(6)->toDateString(),
            'notes' => 'Rencana liburan bersama keluarga',
        ]);

        $goal = SavingsGoal::where('name', 'Dana Liburan Jepang')->first();
        $this->assertNotNull($goal);
        $this->assertEquals($this->user->id, $goal->user_id);
        $this->assertEquals(25000000.00, (float) $goal->target_amount);
        $this->assertEquals('active', $goal->status);

        $response->assertRedirect(route('savings-goals.show', $goal));
        $response->assertSessionHas('success');
    }

    /**
     * User can view savings goal detail.
     */
    public function test_user_can_view_savings_goal_detail(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Dana Darurat 2026',
            'target_amount' => 12000000.00,
        ]);

        $response = $this->actingAs($this->user)->get(route('savings-goals.show', $goal));

        $response->assertOk();
        $response->assertSee('Dana Darurat 2026');
        $response->assertSee('Rp12.000.000');
    }

    /**
     * User can update a savings goal.
     */
    public function test_user_can_update_savings_goal(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Target Awal',
            'target_amount' => 5000000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->put(route('savings-goals.update', $goal), [
            'name' => 'Target Diperbarui',
            'target_amount' => 7000000.00,
            'status' => 'paused',
        ]);

        $response->assertRedirect(route('savings-goals.show', $goal));
        $this->assertDatabaseHas('savings_goals', [
            'id' => $goal->id,
            'name' => 'Target Diperbarui',
            'target_amount' => 7000000.00,
            'status' => 'paused',
        ]);
    }

    /**
     * User can toggle goal status quickly.
     */
    public function test_user_can_toggle_goal_status(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->patch(route('savings-goals.toggle-status', $goal), [
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $this->assertEquals('completed', $goal->fresh()->status);
    }

    /**
     * User can delete a savings goal.
     */
    public function test_user_can_delete_savings_goal(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(route('savings-goals.destroy', $goal));

        $response->assertRedirect(route('savings-goals.index'));
        $this->assertDatabaseMissing('savings_goals', ['id' => $goal->id]);
    }

    /**
     * User can record a contribution to a savings goal.
     */
    public function test_user_can_record_contribution(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'target_amount' => 10000000.00,
        ]);

        $response = $this->actingAs($this->user)->post(route('goal-contributions.store', $goal), [
            'amount' => 1500000.00,
            'contribution_date' => Carbon::now()->toDateString(),
            'wallet_id' => $wallet->id,
            'notes' => 'Gaji bulan ini',
        ]);

        $response->assertRedirect(route('savings-goals.show', $goal));
        $this->assertDatabaseHas('goal_contributions', [
            'user_id' => $this->user->id,
            'savings_goal_id' => $goal->id,
            'wallet_id' => $wallet->id,
            'amount' => 1500000.00,
        ]);

        $this->assertEquals(1500000.00, $goal->fresh()->total_saved);
        $this->assertEquals(8500000.00, $goal->fresh()->remaining_amount);
        $this->assertEquals(15.0, $goal->fresh()->progress_percentage);
    }

    /**
     * User can delete a contribution record.
     */
    public function test_user_can_delete_contribution(): void
    {
        $goal = SavingsGoal::factory()->create(['user_id' => $this->user->id]);
        $contribution = GoalContribution::factory()->create([
            'user_id' => $this->user->id,
            'savings_goal_id' => $goal->id,
            'amount' => 500000.00,
        ]);

        $response = $this->actingAs($this->user)->delete(route('goal-contributions.destroy', $contribution));

        $response->assertRedirect(route('savings-goals.show', $goal->id));
        $this->assertDatabaseMissing('goal_contributions', ['id' => $contribution->id]);
    }

    /**
     * Progress percentage is capped at 100% visually and tracks surplus per PRD 5.8.
     */
    public function test_savings_goal_progress_capped_at_100_percent_with_surplus(): void
    {
        $goal = SavingsGoal::factory()->create([
            'user_id' => $this->user->id,
            'target_amount' => 1000000.00,
        ]);

        // Contribute 1,200,000 (exceeds target by 200,000)
        GoalContribution::factory()->create([
            'user_id' => $this->user->id,
            'savings_goal_id' => $goal->id,
            'amount' => 1200000.00,
        ]);

        $freshGoal = $goal->fresh();
        $this->assertEquals(1200000.00, $freshGoal->total_saved);
        $this->assertEquals(0.00, $freshGoal->remaining_amount);
        $this->assertEquals(100.0, $freshGoal->progress_percentage); // Capped at 100% visually
        $this->assertEquals(120.0, $freshGoal->actual_percentage);
        $this->assertEquals(200000.00, $freshGoal->surplus_amount);
        $this->assertTrue($freshGoal->is_target_reached);
    }

    /**
     * Multi-tenant isolation: User A cannot view, update, delete, or contribute to User B's goal.
     */
    public function test_savings_goal_multi_tenant_isolation(): void
    {
        $otherUser = User::factory()->create();
        $otherGoal = SavingsGoal::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Target Rahasia User Lain',
        ]);
        $otherContribution = GoalContribution::factory()->create([
            'user_id' => $otherUser->id,
            'savings_goal_id' => $otherGoal->id,
        ]);

        // User A cannot view other user's goal
        $this->actingAs($this->user)
            ->get(route('savings-goals.show', $otherGoal))
            ->assertForbidden();

        // User A cannot edit other user's goal
        $this->actingAs($this->user)
            ->get(route('savings-goals.edit', $otherGoal))
            ->assertForbidden();

        // User A cannot update other user's goal
        $this->actingAs($this->user)
            ->put(route('savings-goals.update', $otherGoal), [
                'name' => 'Hacked',
                'target_amount' => 1000.00,
                'status' => 'active',
            ])
            ->assertForbidden();

        // User A cannot delete other user's goal
        $this->actingAs($this->user)
            ->delete(route('savings-goals.destroy', $otherGoal))
            ->assertForbidden();

        // User A cannot contribute to other user's goal
        $this->actingAs($this->user)
            ->post(route('goal-contributions.store', $otherGoal), [
                'amount' => 50000.00,
                'contribution_date' => Carbon::now()->toDateString(),
            ])
            ->assertForbidden();

        // User A cannot delete other user's contribution
        $this->actingAs($this->user)
            ->delete(route('goal-contributions.destroy', $otherContribution))
            ->assertForbidden();
    }
}
