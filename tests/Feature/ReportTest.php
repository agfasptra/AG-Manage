<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Guest cannot access reports or export endpoints.
     */
    public function test_guest_cannot_access_reports_or_export(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('reports.export'))->assertRedirect(route('login'));
    }

    /**
     * Authenticated user can view reports page.
     */
    public function test_authenticated_user_can_view_reports(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertSee('Laporan Keuangan');
        $response->assertSee('Ekspor CSV');
    }

    /**
     * Reports accurately calculate income, expenses, and net cash flow while keeping transfers neutral.
     */
    public function test_reports_calculate_totals_and_exclude_transfers(): void
    {
        $walletA = Wallet::factory()->create(['user_id' => $this->user->id]);
        $walletB = Wallet::factory()->create(['user_id' => $this->user->id]);
        $catIncome = Category::factory()->income()->create(['user_id' => $this->user->id]);
        $catExpense = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $now = Carbon::now()->toDateString();

        // 1. Income: 2,000,000
        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $walletA->id,
            'category_id' => $catIncome->id,
            'amount' => 2000000.00,
            'transaction_date' => $now,
        ]);

        // 2. Expense: 750,000
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $walletA->id,
            'category_id' => $catExpense->id,
            'amount' => 750000.00,
            'transaction_date' => $now,
        ]);

        // 3. Transfer: 500,000 (must be neutral to income/expense)
        Transaction::factory()->transfer()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $walletA->id,
            'target_wallet_id' => $walletB->id,
            'amount' => 500000.00,
            'transaction_date' => $now,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalIncome', 2000000.00);
        $response->assertViewHas('totalExpense', 750000.00);
        $response->assertViewHas('netCashFlow', 1250000.00);
        $response->assertViewHas('totalTransfer', 500000.00);

        $response->assertSee('Rp2.000.000');
        $response->assertSee('Rp750.000');
        $response->assertSee('Rp1.250.000');
        $response->assertSee('Rp500.000');
    }

    /**
     * Reports calculate accurate MoM comparison with previous period.
     */
    public function test_reports_calculate_accurate_mom_comparison(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $catIncome = Category::factory()->income()->create(['user_id' => $this->user->id]);
        $catExpense = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        $startThisMonth = Carbon::now()->startOfMonth();

        // Current month
        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catIncome->id,
            'amount' => 1500000.00,
            'transaction_date' => $startThisMonth->copy()->addDays(2)->toDateString(),
        ]);
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catExpense->id,
            'amount' => 400000.00,
            'transaction_date' => $startThisMonth->copy()->addDays(3)->toDateString(),
        ]);

        // Previous month
        $startPrevMonth = Carbon::now()->subMonth()->startOfMonth();
        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catIncome->id,
            'amount' => 1000000.00,
            'transaction_date' => $startPrevMonth->copy()->addDays(2)->toDateString(),
        ]);
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $catExpense->id,
            'amount' => 500000.00,
            'transaction_date' => $startPrevMonth->copy()->addDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index', ['preset' => 'current_month']));

        $response->assertOk();
        $comparison = $response->viewData('comparison');

        $this->assertEquals(500000.00, $comparison['income_diff']); // 1.5m - 1.0m = +500k
        $this->assertEquals(50.0, $comparison['income_pct']);       // +50%
        $this->assertEquals(-100000.00, $comparison['expense_diff']); // 400k - 500k = -100k
        $this->assertEquals(-20.0, $comparison['expense_pct']);     // -20%
    }

    /**
     * Reports filter by wallet, category, and date range.
     */
    public function test_reports_filter_by_wallet_category_and_date(): void
    {
        $wallet1 = Wallet::factory()->create(['user_id' => $this->user->id, 'name' => 'Dompet Alpha']);
        $wallet2 = Wallet::factory()->create(['user_id' => $this->user->id, 'name' => 'Dompet Beta']);
        $cat1 = Category::factory()->expense()->create(['user_id' => $this->user->id, 'name' => 'Makanan']);
        $cat2 = Category::factory()->expense()->create(['user_id' => $this->user->id, 'name' => 'Elektronik']);

        // Transaction matching filter: Wallet 1, Cat 1, in range
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet1->id,
            'category_id' => $cat1->id,
            'amount' => 150000.00,
            'transaction_date' => '2026-05-10',
            'notes' => 'Matching Record',
        ]);

        // Transaction matching wallet 2 (should be excluded when filtered to wallet 1)
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet2->id,
            'category_id' => $cat1->id,
            'amount' => 990000.00,
            'transaction_date' => '2026-05-10',
            'notes' => 'Wrong Wallet',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index', [
            'preset' => 'custom',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
            'wallet_id' => $wallet1->id,
            'category_id' => $cat1->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('totalExpense', 150000.00);
        $response->assertSee('Matching Record');
        $response->assertDontSee('Wrong Wallet');
    }

    /**
     * Category expense breakdown aggregates correctly.
     */
    public function test_reports_category_breakdown(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $foodCat = Category::factory()->expense()->create(['user_id' => $this->user->id, 'name' => 'Kategori Makanan']);
        $transCat = Category::factory()->expense()->create(['user_id' => $this->user->id, 'name' => 'Kategori Transport']);

        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $foodCat->id,
            'amount' => 300000.00,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $transCat->id,
            'amount' => 100000.00,
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $breakdown = $response->viewData('expenseByCategory');

        $this->assertCount(2, $breakdown);
        $this->assertEquals('Kategori Makanan', $breakdown[0]['name']);
        $this->assertEquals(300000.00, $breakdown[0]['total']);
        $this->assertEquals(75.0, $breakdown[0]['percentage']); // 300k / 400k = 75%

        $this->assertEquals('Kategori Transport', $breakdown[1]['name']);
        $this->assertEquals(100000.00, $breakdown[1]['total']);
        $this->assertEquals(25.0, $breakdown[1]['percentage']); // 100k / 400k = 25%
    }

    /**
     * CSV export streams valid CSV with UTF-8 BOM, headers, and correct transaction data.
     */
    public function test_export_csv_streams_valid_csv(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id, 'name' => 'BCA Rekening']);
        $category = Category::factory()->income()->create(['user_id' => $this->user->id, 'name' => 'Gaji Pokok']);

        Transaction::factory()->income()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 7500000.00,
            'transaction_date' => Carbon::now()->toDateString(),
            'notes' => 'Gaji Bulanan PT Maju',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.export'));

        $response->assertOk();
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="laporan-keuangan-', $response->headers->get('Content-Disposition'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // 1. Verify UTF-8 BOM is present
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // 2. Verify column headings
        $this->assertStringContainsString('ID Transaksi', $content);
        $this->assertStringContainsString('Tanggal', $content);
        $this->assertStringContainsString('Nominal (Rp)', $content);

        // 3. Verify transaction record
        $this->assertStringContainsString('Pemasukan', $content);
        $this->assertStringContainsString('Gaji Pokok', $content);
        $this->assertStringContainsString('BCA Rekening', $content);
        $this->assertStringContainsString('7500000.00', $content);
        $this->assertStringContainsString('Gaji Bulanan PT Maju', $content);
    }

    /**
     * CSV export sanitizes malicious strings to prevent CSV formula injection (PRD 5.10 & Security Rule 6).
     */
    public function test_export_csv_neutralizes_formula_injection(): void
    {
        $wallet = Wallet::factory()->create(['user_id' => $this->user->id]);
        $category = Category::factory()->expense()->create(['user_id' => $this->user->id]);

        // Malicious notes starting with =, +, -, @
        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 50000.00,
            'transaction_date' => Carbon::now()->toDateString(),
            'notes' => "=cmd|' /C calc'!A0",
        ]);

        Transaction::factory()->expense()->create([
            'user_id' => $this->user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => 60000.00,
            'transaction_date' => Carbon::now()->toDateString(),
            'notes' => '@SUM(A1:B10)',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.export'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // Must be prepended with single quote to neutralize spreadsheet execution
        $this->assertStringContainsString("'=cmd|' /C calc'!A0", $content);
        $this->assertStringContainsString("'@SUM(A1:B10)", $content);
    }

    /**
     * Multi-tenant isolation: User A cannot view or export User B's transactions in reports.
     */
    public function test_reports_and_export_multi_tenant_isolation(): void
    {
        $otherUser = User::factory()->create(['name' => 'User Rahasia B']);
        $otherWallet = Wallet::factory()->create(['user_id' => $otherUser->id, 'name' => 'Dompet Rahasia B']);
        $otherCat = Category::factory()->expense()->create(['user_id' => $otherUser->id, 'name' => 'Kategori Rahasia B']);

        Transaction::factory()->expense()->create([
            'user_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'category_id' => $otherCat->id,
            'amount' => 99999999.00,
            'transaction_date' => Carbon::now()->toDateString(),
            'notes' => 'Catatan Rahasia User Lain',
        ]);

        // 1. Report page isolation
        $pageResponse = $this->actingAs($this->user)->get(route('reports.index'));
        $pageResponse->assertOk();
        $pageResponse->assertDontSee('Dompet Rahasia B');
        $pageResponse->assertDontSee('Kategori Rahasia B');
        $pageResponse->assertDontSee('Catatan Rahasia User Lain');
        $pageResponse->assertDontSee('Rp99.999.999');

        // 2. CSV export isolation
        $exportResponse = $this->actingAs($this->user)->get(route('reports.export'));
        ob_start();
        $exportResponse->sendContent();
        $csvContent = ob_get_clean();

        $this->assertStringNotContainsString('Dompet Rahasia B', $csvContent);
        $this->assertStringNotContainsString('Kategori Rahasia B', $csvContent);
        $this->assertStringNotContainsString('Catatan Rahasia User Lain', $csvContent);
        $this->assertStringNotContainsString('99999999.00', $csvContent);
    }
}
