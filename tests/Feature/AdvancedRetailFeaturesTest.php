<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\GoodsReceivedNote;
use App\Models\HeldSale;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedRetailFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Branch $branch;
    protected Register $register;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(SystemDataSeeder::class);

        $this->admin = User::where('username', 'admin')->first();
        $this->cashier = User::where('username', 'cashier')->first();
        $this->branch = Branch::where('code', 'BR-COL-01')->first();
        $this->register = Register::where('branch_id', $this->branch->id)->first();
    }

    public function test_hold_and_resume_cart(): void
    {
        $variation = ProductVariation::first();

        // 1. Hold a sale
        $holdData = [
            'reference' => 'Table #05 - Nuwan',
            'note' => 'Left wallet in car, back in 5 mins',
            'subtotal' => 3600.00,
            'grand_total' => 3600.00,
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => 2,
                    'unit_price' => 1800.00,
                ],
            ],
        ];

        $holdResponse = $this->actingAs($this->admin)->post(route('pos.hold'), $holdData);
        $holdResponse->assertStatus(200);
        $holdResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('held_sales', [
            'reference' => 'Table #05 - Nuwan',
            'status' => 'held',
        ]);

        $heldSale = HeldSale::where('reference', 'Table #05 - Nuwan')->first();
        $this->assertCount(1, $heldSale->items);

        // 2. Fetch list of active held sales
        $listResponse = $this->actingAs($this->admin)->get(route('pos.held-sales'));
        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['reference' => 'Table #05 - Nuwan']);

        // 3. Resume the held sale
        $resumeResponse = $this->actingAs($this->admin)->post(route('pos.held-sales.resume', $heldSale->id));
        $resumeResponse->assertStatus(200);
        $resumeResponse->assertJson(['success' => true]);

        $heldSale->refresh();
        $this->assertEquals('resumed', $heldSale->status);
    }

    public function test_pos_split_payment_and_customer_credit(): void
    {
        // Open shift first
        $this->actingAs($this->admin)->post(route('pos.shift.start'), [
            'register_id' => $this->register->id,
            'opening_float' => 5000.00,
        ]);

        $customer = Customer::create([
            'name' => 'Dilshan Silva',
            'phone' => '0712345678',
            'credit_limit' => 25000.00,
            'current_balance' => 0.00,
            'loyalty_points' => 50,
        ]);

        $variation = ProductVariation::first();

        // Checkout Rs. 6000: Paid with Cash 2000 + Card 2000 + Credit 2000
        $checkoutData = [
            'customer_id' => $customer->id,
            'discount_type' => 'fixed',
            'discount_amount' => 0.00,
            'tax_rate' => 0.00,
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => 2,
                    'unit_price' => 3000.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 2000.00, 'tender_amount' => 2000.00],
                ['method' => 'card', 'amount' => 2000.00, 'tender_amount' => 2000.00],
                ['method' => 'credit', 'amount' => 2000.00, 'tender_amount' => 2000.00],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('pos.checkout'), $checkoutData);
        $response->assertRedirect();

        // Customer's outstanding balance increased by credit amount (Rs. 2000)
        $customer->refresh();
        $this->assertEquals(2000.00, (float)$customer->current_balance);

        $sale = Sale::latest()->first();
        $this->assertEquals('partial', $sale->payment_status);
        $this->assertCount(3, $sale->payments);
    }

    public function test_customer_credit_settlement(): void
    {
        $customer = Customer::create([
            'name' => 'Nadeesha Senanayake',
            'phone' => '0778899001',
            'credit_limit' => 50000.00,
            'current_balance' => 15000.00,
            'loyalty_points' => 120,
        ]);

        $response = $this->actingAs($this->admin)->post(route('customers.settle', $customer->id), [
            'amount' => 10000.00,
            'payment_method' => 'bank_transfer',
            'reference_no' => 'TXN-COMBANK-44910',
            'notes' => 'Settled via online fund transfer',
        ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('status');

        $customer->refresh();
        $this->assertEquals(5000.00, (float)$customer->current_balance);

        $this->assertDatabaseHas('customer_payments', [
            'customer_id' => $customer->id,
            'amount' => 10000.00,
            'payment_method' => 'bank_transfer',
            'reference_no' => 'TXN-COMBANK-44910',
        ]);
    }

    public function test_fifo_batch_deductions_in_pos(): void
    {
        // Open shift
        $this->actingAs($this->admin)->post(route('pos.shift.start'), [
            'register_id' => $this->register->id,
            'opening_float' => 5000.00,
        ]);

        $variation = ProductVariation::first();

        // Clear existing stock for this variation in this branch
        Stock::where('branch_id', $this->branch->id)->where('product_variation_id', $variation->id)->delete();

        // Batch A (Expiring earlier: in 2 months)
        $batchA = Stock::create([
            'branch_id' => $this->branch->id,
            'product_variation_id' => $variation->id,
            'batch_number' => 'BATCH-A-2M',
            'exp_date' => date('Y-m-d', strtotime('+2 months')),
            'quantity' => 10,
            'reorder_level' => 2,
        ]);

        // Batch B (Expiring later: in 12 months)
        $batchB = Stock::create([
            'branch_id' => $this->branch->id,
            'product_variation_id' => $variation->id,
            'batch_number' => 'BATCH-B-12M',
            'exp_date' => date('Y-m-d', strtotime('+12 months')),
            'quantity' => 10,
            'reorder_level' => 2,
        ]);

        // Sell 14 items
        $checkoutData = [
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_rate' => 0,
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => 14,
                    'unit_price' => 1000.00,
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 14000.00, 'tender_amount' => 14000.00],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('pos.checkout'), $checkoutData);
        $response->assertRedirect();

        // FIFO verification: Batch A was fully exhausted (10 - 10 = 0)
        $batchA->refresh();
        $this->assertEquals(0, $batchA->quantity);

        // Batch B had remaining 4 deducted (10 - 4 = 6)
        $batchB->refresh();
        $this->assertEquals(6, $batchB->quantity);
    }

    public function test_supplier_returns_and_debit_note(): void
    {
        $supplier = Supplier::first();
        $initialBalance = (float)$supplier->current_balance;
        $variation = ProductVariation::first();

        $returnResponse = $this->actingAs($this->admin)->post(route('procurement.returns.store'), [
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'reason' => 'Defective cosmetics batch returned to vendor',
            'credit_note_no' => 'CN-VEND-8821',
            'items' => [
                [
                    'product_variation_id' => $variation->id,
                    'batch_number' => 'DEFECT-001',
                    'quantity' => 4,
                    'unit_cost' => 1500.00,
                ],
            ],
        ]);

        $returnResponse->assertRedirect(route('procurement.returns'));
        $returnResponse->assertSessionHas('status');

        $this->assertDatabaseHas('purchase_returns', [
            'supplier_id' => $supplier->id,
            'total_amount' => 6000.00,
            'credit_note_no' => 'CN-VEND-8821',
        ]);

        // Supplier payable liability decreased by return amount
        $supplier->refresh();
        $this->assertEquals($initialBalance - 6000.00, (float)$supplier->current_balance);
    }

    public function test_barcode_generator_page_render(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.barcodes'));
        $response->assertStatus(200);
        $response->assertSee('Barcode Label Printing', false);
        $response->assertSee('50mm x 30mm', false);
    }

    public function test_reports_shifts_and_csv_export(): void
    {
        // 1. Shift Z-reports index
        $shiftsResponse = $this->actingAs($this->admin)->get(route('reports.shifts'));
        $shiftsResponse->assertStatus(200);
        $shiftsResponse->assertSee('Cashier Shift Z-Reports', false);

        // 2. Date-filtered reports index
        $todayReportResponse = $this->actingAs($this->admin)->get(route('reports.index', ['date_filter' => 'today']));
        $todayReportResponse->assertStatus(200);
        $todayReportResponse->assertSee('Net Sales Revenue', false);

        // 3. CSV Export
        $csvResponse = $this->actingAs($this->admin)->get(route('reports.export-csv'));
        $csvResponse->assertStatus(200);
        $this->assertTrue(str_contains($csvResponse->headers->get('content-type'), 'text/csv'));
    }

    public function test_manager_pin_verification(): void
    {
        // Valid PIN for admin
        $validResponse = $this->actingAs($this->cashier)->post(route('pos.verify-pin'), [
            'pin' => '1234',
        ]);
        $validResponse->assertStatus(200);
        $validResponse->assertJson(['success' => true]);

        // Invalid PIN
        $invalidResponse = $this->actingAs($this->cashier)->post(route('pos.verify-pin'), [
            'pin' => '9999',
        ]);
        $invalidResponse->assertStatus(403);
        $invalidResponse->assertJson(['success' => false]);
    }

    public function test_admin_backup_download(): void
    {
        $backupResponse = $this->actingAs($this->admin)->get(route('admin.backup.download'));
        $backupResponse->assertStatus(200);
        $this->assertTrue(str_contains($backupResponse->headers->get('content-type'), 'application/json'));
    }

    public function test_serial_number_ingestion_and_warranty_lookup(): void
    {
        $variation = ProductVariation::first();
        $variation->update(['track_serial' => true, 'warranty_months' => 24]);

        // 1. Ingest bulk serials
        $postResponse = $this->actingAs($this->admin)->post(route('inventory.serials.store'), [
            'product_variation_id' => $variation->id,
            'branch_id' => $this->branch->id,
            'warranty_months' => 24,
            'serials_text' => "IMEI-TEST-0001\nIMEI-TEST-0002\nIMEI-TEST-0003",
        ]);

        $postResponse->assertRedirect(route('inventory.serials'));
        $postResponse->assertSessionHas('status');

        $this->assertDatabaseHas('product_serials', [
            'serial_number' => 'IMEI-TEST-0001',
            'product_variation_id' => $variation->id,
            'status' => 'in_stock',
        ]);

        // 2. Lookup warranty by serial
        $lookupResponse = $this->actingAs($this->cashier)->get(route('warranty.lookup', ['serial' => 'IMEI-TEST-0001']));
        $lookupResponse->assertStatus(200);
        $lookupResponse->assertSee('IMEI-TEST-0001', false);
        $lookupResponse->assertSee('IN STOCK', false);
    }

    public function test_pos_checkout_with_serial_allocation_and_escpos_receipt(): void
    {
        CashierShift::create([
            'user_id' => $this->cashier->id,
            'register_id' => $this->register->id,
            'branch_id' => $this->branch->id,
            'opening_balance' => 5000.00,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $variation = ProductVariation::first();
        $variation->update(['track_serial' => true, 'warranty_months' => 12]);

        $serial = ProductSerial::create([
            'product_variation_id' => $variation->id,
            'branch_id' => $this->branch->id,
            'serial_number' => 'SN-MOBILE-8877',
            'status' => 'in_stock',
            'warranty_months' => 12,
        ]);

        // Stock must exist
        Stock::updateOrCreate(
            ['branch_id' => $this->branch->id, 'product_variation_id' => $variation->id],
            ['quantity' => 10]
        );

        // POS checkout specifying the serial
        $checkoutData = [
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_rate' => 0,
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => 1,
                    'unit_price' => 45000.00,
                    'serials' => ['SN-MOBILE-8877'],
                ],
            ],
            'payments' => [
                ['method' => 'cash', 'amount' => 45000.00, 'tender_amount' => 50000.00],
            ],
        ];

        $checkoutResponse = $this->actingAs($this->cashier)->post(route('pos.checkout'), $checkoutData);
        $checkoutResponse->assertRedirect();

        // Serial status must be sold and warranty expires in 12 months
        $serial->refresh();
        $this->assertEquals('sold', $serial->status);
        $this->assertNotNull($serial->sale_id);
        $this->assertNotNull($serial->warranty_expires_at);

        // Verify warranty lookup shows Sold & Warranty Active
        $lookupResponse = $this->actingAs($this->cashier)->get(route('warranty.lookup', ['serial' => 'SN-MOBILE-8877']));
        $lookupResponse->assertStatus(200);
        $lookupResponse->assertSee('SN-MOBILE-8877', false);
        $lookupResponse->assertSee('Active Warranty Valid', false);

        // Verify ESC/POS binary receipt download
        $escposResponse = $this->actingAs($this->cashier)->get(route('pos.receipt.escpos', $serial->sale_id));
        $escposResponse->assertStatus(200);
        $this->assertEquals('application/octet-stream', $escposResponse->headers->get('content-type'));
        $content = $escposResponse->getContent();
        $this->assertStringContainsString('SN-MOBILE-8877', $content);
    }

    public function test_cash_drawer_kick_endpoint(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('pos.open-drawer'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('raw_base64'));
    }
}
