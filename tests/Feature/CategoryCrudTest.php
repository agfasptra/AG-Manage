<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->seed(DefaultCategorySeeder::class);
    }

    // ─── INDEX ──────────────────────────────────────────

    public function test_guest_cannot_access_categories(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_see_category_list(): void
    {
        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Gaji')        // default income category
            ->assertSee('Makanan');     // default expense category (partial)
    }

    public function test_user_sees_own_custom_categories(): void
    {
        $custom = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Ojol',
            'type' => 'expense',
            'color' => '#FF5733',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertSee('Ojol');
    }

    public function test_user_does_not_see_other_users_custom_categories(): void
    {
        $otherUser = User::factory()->create();
        Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Secret Cat',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertDontSee('Secret Cat');
    }

    // ─── CREATE / STORE ─────────────────────────────────

    public function test_user_can_view_create_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('categories.create'))
            ->assertOk()
            ->assertSee('Tambah Kategori');
    }

    public function test_user_can_create_income_category(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Bonus',
                'type' => 'income',
                'color' => '#16A34A',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Bonus',
            'type' => 'income',
        ]);
    }

    public function test_user_can_create_expense_category(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Jajan',
                'type' => 'expense',
                'color' => '#DC2626',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'user_id' => $this->user->id,
            'name' => 'Jajan',
            'type' => 'expense',
        ]);
    }

    public function test_category_validation_rejects_missing_name(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => '',
                'type' => 'income',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_category_validation_rejects_invalid_type(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Test',
                'type' => 'transfer',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_category_validation_rejects_invalid_color(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Test',
                'type' => 'income',
                'color' => 'not-a-color',
            ])
            ->assertSessionHasErrors('color');
    }

    // ─── EDIT / UPDATE ──────────────────────────────────

    public function test_user_can_edit_own_category(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Ojol',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('categories.edit', $category))
            ->assertOk();
    }

    public function test_user_can_update_own_category(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Ojol',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->put(route('categories.update', $category), [
                'name' => 'Ojek Online',
                'type' => 'expense',
                'color' => '#EA580C',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $category->refresh();
        $this->assertEquals('Ojek Online', $category->name);
    }

    public function test_user_cannot_edit_system_default_category(): void
    {
        $default = Category::whereNull('user_id')->first();

        $this->actingAs($this->user)
            ->get(route('categories.edit', $default))
            ->assertForbidden();
    }

    public function test_user_cannot_update_system_default_category(): void
    {
        $default = Category::whereNull('user_id')->first();

        $this->actingAs($this->user)
            ->put(route('categories.update', $default), [
                'name' => 'Hacked',
                'type' => 'income',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_update_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->put(route('categories.update', $category), [
                'name' => 'Hacked',
                'type' => 'expense',
            ])
            ->assertForbidden();
    }

    // ─── TOGGLE ACTIVE ──────────────────────────────────

    public function test_user_can_deactivate_own_category(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Test',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->patch(route('categories.toggle-active', $category))
            ->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertFalse($category->is_active);
    }

    public function test_user_can_reactivate_own_category(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Test',
            'type' => 'expense',
            'is_active' => false,
        ]);

        $this->actingAs($this->user)
            ->patch(route('categories.toggle-active', $category))
            ->assertRedirect(route('categories.index'));

        $category->refresh();
        $this->assertTrue($category->is_active);
    }

    public function test_user_cannot_toggle_system_default_category(): void
    {
        $default = Category::whereNull('user_id')->first();

        $this->actingAs($this->user)
            ->patch(route('categories.toggle-active', $default))
            ->assertForbidden();
    }

    // ─── DELETE ─────────────────────────────────────────

    public function test_user_can_delete_own_category_without_transactions(): void
    {
        $category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Temp',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_user_cannot_delete_system_default_category(): void
    {
        $default = Category::whereNull('user_id')->first();

        $this->actingAs($this->user)
            ->delete(route('categories.destroy', $default))
            ->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_category(): void
    {
        $otherUser = User::factory()->create();
        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();
    }

    // ─── SCOPE ──────────────────────────────────────────

    public function test_accessible_by_scope_returns_defaults_and_user_categories(): void
    {
        $custom = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Custom Cat',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $accessible = Category::accessibleBy($this->user->id)->get();

        // Should include 14 defaults + 1 custom
        $this->assertCount(15, $accessible);
        $this->assertTrue($accessible->contains('name', 'Custom Cat'));
        $this->assertTrue($accessible->contains('name', 'Gaji'));
    }
}
