<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'GoPay', 'OVO', 'DANA', 'Tunai', 'ShopeePay']),
            'type' => fake()->randomElement(['cash', 'bank', 'ewallet', 'other']),
            'opening_balance' => fake()->numberBetween(0, 10000000),
            'currency' => 'IDR',
            'description' => fake()->optional(0.5)->sentence(),
            'is_archived' => false,
        ];
    }

    /**
     * Indicate that the wallet is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }

    /**
     * Set the wallet type to cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cash',
        ]);
    }

    /**
     * Set the wallet type to bank.
     */
    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bank',
        ]);
    }

    /**
     * Set the wallet type to e-wallet.
     */
    public function ewallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ewallet',
        ]);
    }
}
