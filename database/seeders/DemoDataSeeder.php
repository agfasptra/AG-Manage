<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the demo data seeds for development and testing.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@agmanage.id')->first();

        if (! $demoUser) {
            return;
        }

        // Only seed demo data if the user has no wallets yet
        if ($demoUser->wallets()->exists()) {
            return;
        }

        DB::transaction(function () use ($demoUser): void {
            // 1. Create Wallets
            $cashWallet = Wallet::create([
                'user_id' => $demoUser->id,
                'name' => 'Kas Tunai',
                'type' => 'cash',
                'opening_balance' => 750000.00,
                'is_archived' => false,
            ]);

            $bankWallet = Wallet::create([
                'user_id' => $demoUser->id,
                'name' => 'Rekening BCA',
                'type' => 'bank',
                'opening_balance' => 8500000.00,
                'is_archived' => false,
            ]);

            $ewallet = Wallet::create([
                'user_id' => $demoUser->id,
                'name' => 'GoPay',
                'type' => 'ewallet',
                'opening_balance' => 450000.00,
                'is_archived' => false,
            ]);

            // 2. Fetch Default Categories
            $salaryCat = Category::whereNull('user_id')->where('name', 'Gaji')->first();
            $freelanceCat = Category::whereNull('user_id')->where('name', 'Freelance')->first();
            $foodCat = Category::whereNull('user_id')->where('name', 'Makanan & Minuman')->first();
            $transCat = Category::whereNull('user_id')->where('name', 'Transportasi')->first();
            $rentCat = Category::whereNull('user_id')->where('name', 'Tempat Tinggal / Kost')->first();
            $utilityCat = Category::whereNull('user_id')->where('name', 'Tagihan & Utilitas')->first();
            $shopCat = Category::whereNull('user_id')->where('name', 'Belanja')->first();
            $entertainCat = Category::whereNull('user_id')->where('name', 'Hiburan')->first();

            $now = Carbon::now();
            $month = $now->month;
            $year = $now->year;

            // 3. Transactions for current month
            if ($salaryCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $bankWallet->id,
                    'category_id' => $salaryCat->id,
                    'type' => 'income',
                    'amount' => 7500000.00,
                    'transaction_date' => $now->copy()->startOfMonth()->addDays(1)->toDateString(),
                    'notes' => 'Gaji Bulanan',
                ]);
            }

            if ($freelanceCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $bankWallet->id,
                    'category_id' => $freelanceCat->id,
                    'type' => 'income',
                    'amount' => 2000000.00,
                    'transaction_date' => $now->copy()->startOfMonth()->addDays(10)->toDateString(),
                    'notes' => 'Project Desain Web',
                ]);
            }

            if ($rentCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $bankWallet->id,
                    'category_id' => $rentCat->id,
                    'type' => 'expense',
                    'amount' => 1500000.00,
                    'transaction_date' => $now->copy()->startOfMonth()->addDays(2)->toDateString(),
                    'notes' => 'Sewa Kost Bulanan',
                ]);
            }

            if ($utilityCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $bankWallet->id,
                    'category_id' => $utilityCat->id,
                    'type' => 'expense',
                    'amount' => 350000.00,
                    'transaction_date' => $now->copy()->startOfMonth()->addDays(5)->toDateString(),
                    'notes' => 'Listrik & WiFi',
                ]);
            }

            if ($foodCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $cashWallet->id,
                    'category_id' => $foodCat->id,
                    'type' => 'expense',
                    'amount' => 125000.00,
                    'transaction_date' => $now->copy()->subDays(3)->toDateString(),
                    'notes' => 'Makan Siang & Kopi',
                ]);

                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $ewallet->id,
                    'category_id' => $foodCat->id,
                    'type' => 'expense',
                    'amount' => 85000.00,
                    'transaction_date' => $now->copy()->subDays(1)->toDateString(),
                    'notes' => 'Pesan Antar Makan Malam',
                ]);
            }

            if ($transCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $ewallet->id,
                    'category_id' => $transCat->id,
                    'type' => 'expense',
                    'amount' => 150000.00,
                    'transaction_date' => $now->copy()->subDays(4)->toDateString(),
                    'notes' => 'Bensin & Ojek Online',
                ]);
            }

            if ($shopCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $ewallet->id,
                    'category_id' => $shopCat->id,
                    'type' => 'expense',
                    'amount' => 280000.00,
                    'transaction_date' => $now->copy()->subDays(2)->toDateString(),
                    'notes' => 'Kebutuhan Harian Supermarket',
                ]);
            }

            if ($entertainCat) {
                Transaction::create([
                    'user_id' => $demoUser->id,
                    'wallet_id' => $cashWallet->id,
                    'category_id' => $entertainCat->id,
                    'type' => 'expense',
                    'amount' => 75000.00,
                    'transaction_date' => $now->copy()->subDays(5)->toDateString(),
                    'notes' => 'Nonton Bioskop',
                ]);
            }

            // Transfer BCA ke GoPay
            Transaction::create([
                'user_id' => $demoUser->id,
                'wallet_id' => $bankWallet->id,
                'target_wallet_id' => $ewallet->id,
                'type' => 'transfer',
                'amount' => 500000.00,
                'transaction_date' => $now->copy()->subDays(6)->toDateString(),
                'notes' => 'Top Up Saldo GoPay',
            ]);

            // 4. Budgets
            if ($foodCat) {
                Budget::create([
                    'user_id' => $demoUser->id,
                    'category_id' => $foodCat->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'amount' => 2000000.00,
                ]);
            }

            if ($transCat) {
                Budget::create([
                    'user_id' => $demoUser->id,
                    'category_id' => $transCat->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'amount' => 600000.00,
                ]);
            }

            if ($entertainCat) {
                Budget::create([
                    'user_id' => $demoUser->id,
                    'category_id' => $entertainCat->id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'amount' => 400000.00,
                ]);
            }

            // 5. Savings Goals
            $goal1 = SavingsGoal::create([
                'user_id' => $demoUser->id,
                'name' => 'Dana Darurat 6 Bulan',
                'target_amount' => 25000000.00,
                'target_date' => $now->copy()->addMonths(12)->toDateString(),
                'status' => 'active',
                'notes' => 'Target tabungan untuk dana tak terduga',
            ]);

            GoalContribution::create([
                'user_id' => $demoUser->id,
                'savings_goal_id' => $goal1->id,
                'wallet_id' => $bankWallet->id,
                'amount' => 10000000.00,
                'contribution_date' => $now->copy()->subDays(15)->toDateString(),
                'notes' => 'Alokasi awal dana darurat',
            ]);

            $goal2 = SavingsGoal::create([
                'user_id' => $demoUser->id,
                'name' => 'Laptop Kerja Baru',
                'target_amount' => 15000000.00,
                'target_date' => $now->copy()->addMonths(6)->toDateString(),
                'status' => 'active',
                'notes' => 'Upgrade laptop untuk produktivitas',
            ]);

            GoalContribution::create([
                'user_id' => $demoUser->id,
                'savings_goal_id' => $goal2->id,
                'wallet_id' => $bankWallet->id,
                'amount' => 6500000.00,
                'contribution_date' => $now->copy()->subDays(7)->toDateString(),
                'notes' => 'Tabungan dari freelance',
            ]);
        });
    }
}
