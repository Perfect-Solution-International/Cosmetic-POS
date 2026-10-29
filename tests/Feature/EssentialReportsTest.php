<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\Stock;
use App\Models\User;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssentialReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Branch $branch;
    protected Register $register;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SystemDataSeeder::class);

        $this->admin = User::where('username', 'admin')->first();
        $this->branch = Branch::where('code', 'BR-COL-01')->first();
        $this->register = Register::where('branch_id', $this->branch->id)->first();
    }

    public function test_cashier_shifts_page_renders_with_summary_kpis(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.shifts'));

        $response->assertStatus(200);
        $response->assertSeeText('Daily Cashier Shift Z-Reports', false);
        $response->assertSeeText('Cash Inflow', false);
        $response->assertSeeText('Net Discrepancy', false);
        $response->assertSeeText('Download Excel Sheet', false);
    }

    public function test_shift_z_report_print_view_renders_successfully(): void
    {
        $shift = CashierShift::create([
            'branch_id' => $this->branch->id,
            'register_id' => $this->register->id,
            'user_id' => $this->admin->id,
            'opening_float' => 5000.00,
            'cash_sales' => 12500.00,
            'card_sales' => 8400.00,
            'other_sales' => 0.00,
            'expected_cash' => 17500.00,
            'closing_cash' => 17500.00,
            'cash_discrepancy' => 0.00,
            'status' => 'closed',
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.shifts.print', $shift->id));

        $response->assertStatus(200);
        $response->assertSeeText('OFFICIAL CASHIER SHIFT Z-REPORT', false);
        $response->assertSeeText('CASH DRAWER AUDIT & RECONCILIATION', false);
        $response->assertSeeText('BALANCED (Rs. 0.00)', false);
        $response->assertSeeText("Cashier's Signature", false);
    }

    public function test_shift_z_reports_excel_export_downloads_valid_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.shifts.export-excel'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('Cashier_Shift_Z_Reports_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_weekly_reorder_and_expiry_report_screen_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.reorder_expiry'));

        $response->assertStatus(200);
        $response->assertSeeText('Weekly Stock Reorder & Batch Expiry Audit', false);
        $response->assertSeeText('Weekly Stock Replenishment & Reorder Procurement Schedule', false);
        $response->assertSeeText('Batch Expiry Risk & Quarantine Schedule', false);
        $response->assertSeeText('Download Excel Sheet', false);
    }

    public function test_weekly_reorder_and_expiry_print_view_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.reorder_expiry.print'));

        $response->assertStatus(200);
        $response->assertSeeText('PSI ONE GLOBAL POS', false);
        $response->assertSeeText('WEEKLY REORDER AUDIT', false);
        $response->assertSeeText('STOCK REPLENISHMENT', false);
        $response->assertSeeText('FEFO AUDIT', false);
        $response->assertSeeText('Procurement Director', false);
    }

    public function test_weekly_reorder_and_expiry_excel_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.reorder_expiry.export-excel'));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('Weekly_Stock_Reorder_Expiry_Report_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_monthly_profit_and_loss_excel_export_downloads_xlsx(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.pnl.export-excel', ['date_filter' => 'this_month']));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('Profit_and_Loss_Statement_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }
}
