<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'target_wallet_id' => null,
            'category_id' => Category::factory()->expense(),
            'type' => 'expense',
            'amount' => fake()->randomFloat(2, 10000, 1000000),
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'notes' => fake()->optional(0.7)->sentence(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
            'target_wallet_id' => null,
            'category_id' => Category::factory()->income(),
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'target_wallet_id' => null,
            'category_id' => Category::factory()->expense(),
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'category_id' => null,
            'target_wallet_id' => Wallet::factory(),
        ]);
    }
}
