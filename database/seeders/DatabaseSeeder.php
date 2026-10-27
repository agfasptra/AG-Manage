<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DefaultCategorySeeder::class);

        if (! User::where('email', 'demo@agmanage.id')->exists()) {
            User::factory()->create([
                'name' => 'Pengguna Demo',
                'email' => 'demo@agmanage.id',
                'password' => Hash::make('password123'),
            ]);
        }

        $this->call(DemoDataSeeder::class);
    }
}
