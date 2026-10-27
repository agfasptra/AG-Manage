<?php

namespace Database\Factories;

use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalContribution>
 */
class GoalContributionFactory extends Factory
{
    protected $model = GoalContribution::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'savings_goal_id' => SavingsGoal::factory(),
            'wallet_id' => Wallet::factory(),
            'amount' => fake()->randomFloat(2, 50000, 1000000),
            'contribution_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
