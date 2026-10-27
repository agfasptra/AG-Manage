<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Pemasukan
            ['user_id' => null, 'name' => 'Gaji', 'type' => 'income', 'icon' => 'wallet', 'color' => '#16A34A'],
            ['user_id' => null, 'name' => 'Uang Saku', 'type' => 'income', 'icon' => 'coins', 'color' => '#059669'],
            ['user_id' => null, 'name' => 'Freelance', 'type' => 'income', 'icon' => 'laptop', 'color' => '#0D9488'],
            ['user_id' => null, 'name' => 'Investasi & Bunga', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#0284C7'],
            ['user_id' => null, 'name' => 'Pemasukan Lainnya', 'type' => 'income', 'icon' => 'plus-circle', 'color' => '#64748B'],

            // Pengeluaran
            ['user_id' => null, 'name' => 'Makanan & Minuman', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#DC2626'],
            ['user_id' => null, 'name' => 'Transportasi', 'type' => 'expense', 'icon' => 'car', 'color' => '#EA580C'],
            ['user_id' => null, 'name' => 'Tempat Tinggal / Kost', 'type' => 'expense', 'icon' => 'home', 'color' => '#D97706'],
            ['user_id' => null, 'name' => 'Tagihan & Utilitas', 'type' => 'expense', 'icon' => 'receipt', 'color' => '#B45309'],
            ['user_id' => null, 'name' => 'Pendidikan', 'type' => 'expense', 'icon' => 'book-open', 'color' => '#2563EB'],
            ['user_id' => null, 'name' => 'Kesehatan', 'type' => 'expense', 'icon' => 'heart-pulse', 'color' => '#E11D48'],
            ['user_id' => null, 'name' => 'Belanja', 'type' => 'expense', 'icon' => 'shopping-bag', 'color' => '#9333EA'],
            ['user_id' => null, 'name' => 'Hiburan', 'type' => 'expense', 'icon' => 'film', 'color' => '#7C3AED'],
            ['user_id' => null, 'name' => 'Pengeluaran Lainnya', 'type' => 'expense', 'icon' => 'help-circle', 'color' => '#64748B'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                [
                    'user_id' => $cat['user_id'],
                    'name' => $cat['name'],
                    'type' => $cat['type'],
                ],
                [
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
