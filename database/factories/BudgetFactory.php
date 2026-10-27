<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->expense(),
            'period_month' => (int) now()->format('n'),
            'period_year' => (int) now()->format('Y'),
            'amount' => fake()->randomFloat(2, 500000, 5000000),
        ];
    }
}
