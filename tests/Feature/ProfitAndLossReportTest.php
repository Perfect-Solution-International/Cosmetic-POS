<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitAndLossReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SystemDataSeeder::class);

        $this->admin = User::where('username', 'admin')->first();
        $this->branch = Branch::where('code', 'BR-COL-01')->first();
    }

    public function test_reports_overview_renders_with_pnl_metrics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Financial & Profitability Analytics', false);
        $response->assertSeeText('Profit & Loss (P&L)', false);
        $response->assertSeeText('Income Statement Breakdown (P&L)', false);
        $response->assertSeeText('Category & Department Profitability', false);
    }

    public function test_dedicated_pnl_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.pnl'));

        $response->assertStatus(200);
        $response->assertSeeText('Profit & Loss (P&L) Statement', false);
        $response->assertSeeText('Trading Revenue', false);
        $response->assertSeeText('Cost of Sales', false);
        $response->assertSeeText('Operating Overheads', false);
        $response->assertSeeText('Operating Expenses Breakdown', false);
        $response->assertSeeText('Category Profitability & Margin Contribution', false);
    }

    public function test_pnl_print_view_renders_properly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.pnl.print'));

        $response->assertStatus(200);
        $response->assertSeeText('PSI ONE GLOBAL POS', false);
        $response->assertSeeText('PROFIT & LOSS STATEMENT', false);
        $response->assertSeeText('TRADING ACCOUNT & GROSS PROFIT', false);
        $response->assertSeeText('OPERATING OVERHEADS & STORE EXPENSES', false);
        $response->assertSeeText('Prepared By', false);
        $response->assertSeeText('Checked & Verified By', false);
        $response->assertSeeText('Approved By', false);
    }

    public function test_pnl_csv_export_generates_streamed_report(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.pnl.export-csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('Profit_and_Loss_Statement_', $response->headers->get('content-disposition'));
    }

    public function test_pnl_excel_export_generates_valid_xlsx_file(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.pnl.export-excel'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_pnl_calculates_cogs_and_expenses_accurately(): void
    {
        $category = ExpenseCategory::firstOrCreate(['name' => 'Store Electricity']);
        Expense::create([
            'branch_id' => $this->branch->id,
            'expense_category_id' => $category->id,
            'amount' => 15000.00,
            'expense_date' => now()->toDateString(),
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.pnl', [
            'date_filter' => 'all',
            'branch_id' => $this->branch->id,
        ]));

        $response->assertStatus(200);
        $response->assertSeeText('Store Electricity', false);
        $response->assertSeeText('15,000.00', false);
    }
}
