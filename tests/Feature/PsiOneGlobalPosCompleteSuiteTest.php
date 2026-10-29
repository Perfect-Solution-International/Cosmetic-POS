<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsiOneGlobalPosCompleteSuiteTest extends TestCase
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

    public function test_pos_terminal_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin)->get(route('pos.terminal'));
        $response->assertStatus(200);
        $response->assertSee('Cashier Register', false);
        $response->assertSee('Current Bill', false);
        $response->assertSee('Start Shift to Enable Checkout', false);
    }

    public function test_cashier_shift_lifecycle(): void
    {
        $register = Register::where('branch_id', $this->branch->id)->first();

        // 1. Start Shift
        $startResponse = $this->actingAs($this->admin)->post(route('pos.shift.start'), [
            'register_id' => $register->id,
            'opening_float' => 7500.00,
        ]);
        $startResponse->assertRedirect(route('pos.terminal'));

        $shift = CashierShift::where('user_id', $this->admin->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        $this->assertNotNull($shift);
        $this->assertEquals(7500.00, $shift->opening_float);

        // Verify that with active shift, PAY / COMPLETE BILL is rendered
        $terminalResponse = $this->actingAs($this->admin)->get(route('pos.terminal'));
        $terminalResponse->assertStatus(200);
        $terminalResponse->assertSee('PAY / COMPLETE BILL', false);

        // 2. Close Shift (Z-Report)
        $closeResponse = $this->actingAs($this->admin)->post(route('pos.shift.close', $shift->id), [
            'counted_cash' => 7500.00,
            'notes' => 'Perfect cash balance at end of shift',
        ]);
        $closeResponse->assertRedirect(route('pos.terminal'));

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals(7500.00, $shift->closing_cash);
        $this->assertEquals(0.00, $shift->cash_discrepancy);
    }

    public function test_pos_checkout_flow_and_receipt(): void
    {
        $variation = ProductVariation::first();
        $customer = Customer::first();
        $price = (float) $variation->selling_price;
        $qty = 2;
        $subtotal = $price * $qty;
        $discount = 2.00;
        $payable = $subtotal - $discount;

        $checkoutData = [
            'customer_id' => $customer->id,
            'discount_type' => 'fixed',
            'discount_amount' => $discount,
            'tax_rate' => 0.00,
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                ],
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount' => $payable,
                    'tender_amount' => $subtotal,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('pos.checkout'), $checkoutData);
        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'discount_amount' => $discount,
        ]);

        $sale = Sale::latest()->first();
        $response->assertRedirect(route('pos.receipt', $sale->id));

        // Verify Receipt Render
        $receiptResponse = $this->actingAs($this->admin)->get(route('pos.receipt', $sale->id));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee($sale->invoice_no, false);
        $receiptResponse->assertSee('Perfect Solution International', false);
    }

    public function test_product_catalog_and_creation(): void
    {
        // 1. View Index
        $response = $this->actingAs($this->admin)->get(route('products.index'));
        $response->assertStatus(200);
        $response->assertSee('Product Catalog', false);

        // 2. View Create
        $createResponse = $this->actingAs($this->admin)->get(route('products.create'));
        $createResponse->assertStatus(200);

        // 3. Store Simple Product
        $category = Category::first();
        $storeResponse = $this->actingAs($this->admin)->post(route('products.store'), [
            'name' => 'Rose Gold Eye Shadow',
            'code' => 'PSI-EYE-999',
            'category_id' => $category->id,
            'type' => 'simple',
            'has_expiry' => 1,
            'single_sku' => 'EYE-RG-01',
            'single_barcode' => '8901234567890',
            'single_purchase_cost' => 1200.00,
            'single_selling_price' => 2500.00,
        ]);
        $storeResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['code' => 'PSI-EYE-999']);
        $this->assertDatabaseHas('product_variations', ['sku' => 'EYE-RG-01']);
    }

    public function test_dynamic_attribute_builder(): void
    {
        // 1. View Attributes
        $response = $this->actingAs($this->admin)->get(route('products.attributes'));
        $response->assertStatus(200);
        $response->assertSee('Dynamic Attribute Builder', false);

        // 2. Create Attribute
        $storeAttrResponse = $this->actingAs($this->admin)->post(route('products.attributes.store'), [
            'name' => 'Fragrance Family',
            'type' => 'dropdown',
            'is_variation' => 1,
        ]);
        $storeAttrResponse->assertRedirect(route('products.attributes'));
        $this->assertDatabaseHas('attributes', ['name' => 'Fragrance Family']);
    }

    public function test_inventory_and_batch_expiry(): void
    {
        // 1. View Inventory
        $invResponse = $this->actingAs($this->admin)->get(route('inventory.index'));
        $invResponse->assertStatus(200);
        $invResponse->assertSee('Branch Inventory', false);
        $invResponse->assertSee('Stock Valuation', false);

        // 2. View Batch & Expiry
        $batchResponse = $this->actingAs($this->admin)->get(route('inventory.batch_expiry'));
        $batchResponse->assertStatus(200);
        $batchResponse->assertSee('Cosmetics Batch', false);
        $batchResponse->assertSee('Expiry Monitor', false);

        // 3. View Transfers
        $transfersResponse = $this->actingAs($this->admin)->get(route('inventory.transfers'));
        $transfersResponse->assertStatus(200);
        $transfersResponse->assertSee('Inter-Branch Stock Transfers', false);
    }

    public function test_procurement_suppliers_and_grn(): void
    {
        // 1. Suppliers
        $supResponse = $this->actingAs($this->admin)->get(route('procurement.suppliers'));
        $supResponse->assertStatus(200);
        $supResponse->assertSee('Suppliers', false);
        $supResponse->assertSee('Vendors Directory', false);

        // 2. Purchase Orders
        $poResponse = $this->actingAs($this->admin)->get(route('procurement.po'));
        $poResponse->assertStatus(200);
        $poResponse->assertSee('Purchase Orders', false);

        // 3. Goods Received Note (GRN) with cosmetics batch
        $grnResponse = $this->actingAs($this->admin)->get(route('procurement.grn'));
        $grnResponse->assertStatus(200);
        $grnResponse->assertSee('Goods Received Notes', false);

        $supplier = Supplier::first();
        $variation = ProductVariation::first();

        $storeGrnResponse = $this->actingAs($this->admin)->post(route('procurement.grn.store'), [
            'supplier_id' => $supplier->id,
            'branch_id' => $this->branch->id,
            'supplier_invoice_no' => 'INV-SUP-TEST-1',
            'received_date' => date('Y-m-d'),
            'items' => [
                [
                    'product_variation_id' => $variation->id,
                    'batch_number' => 'BATCH-TEST-2026',
                    'exp_date' => date('Y-m-d', strtotime('+18 months')),
                    'quantity' => 15,
                    'unit_cost' => 1500.00,
                ],
            ],
        ]);
        $storeGrnResponse->assertRedirect(route('procurement.grn'));
        $this->assertDatabaseHas('goods_received_notes', ['supplier_invoice_no' => 'INV-SUP-TEST-1']);
        $this->assertDatabaseHas('stocks', ['batch_number' => 'BATCH-TEST-2026']);

        // Test payment recording / settlement on the created GRN
        $grn = GoodsReceivedNote::where('supplier_invoice_no', 'INV-SUP-TEST-1')->first();
        $this->assertEquals('unpaid', $grn->payment_status);
        $this->assertEquals(0, (float)$grn->paid_amount);

        $initialBalance = (float)$supplier->fresh()->current_balance;

        $paymentResponse = $this->actingAs($this->admin)->post(route('procurement.grn.payment', $grn->id), [
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'amount' => 22500.00,
            'payment_date' => date('Y-m-d'),
            'reference_no' => 'TXN-COMBANK-9912',
            'notes' => 'Settled via online bank transfer',
        ]);
        $paymentResponse->assertRedirect(route('procurement.grn'));
        $paymentResponse->assertSessionHas('status');

        $grn->refresh();
        $this->assertEquals('paid', $grn->payment_status);
        $this->assertEquals('bank_transfer', $grn->payment_method);
        $this->assertEquals(22500.00, (float)$grn->paid_amount);
        $this->assertEquals('TXN-COMBANK-9912', $grn->payment_reference);

        // Supplier balance decreased by paid amount
        $newBalance = (float)$supplier->fresh()->current_balance;
        $this->assertEquals($initialBalance - 22500.00, $newBalance);

        // Supplier payment voucher audit trail recorded
        $this->assertDatabaseHas('supplier_payments', [
            'grn_id' => $grn->id,
            'amount' => 22500.00,
            'payment_method' => 'bank_transfer',
            'reference_no' => 'TXN-COMBANK-9912',
        ]);
    }

    public function test_customer_crm_and_loyalty(): void
    {
        $response = $this->actingAs($this->admin)->get(route('customers.index'));
        $response->assertStatus(200);
        $response->assertSee('Customer CRM', false);
        $response->assertSee('Loyalty Program', false);

        $createResponse = $this->actingAs($this->admin)->post(route('customers.store'), [
            'name' => 'Kavindi Perera',
            'phone' => '0779988776',
            'email' => 'kavindi@gmail.com',
            'credit_limit' => 25000.00,
        ]);
        $createResponse->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['phone' => '0779988776']);
    }

    public function test_sales_ledger_and_returns(): void
    {
        $sale = Sale::first();
        $response = $this->actingAs($this->admin)->get(route('sales.index'));
        $response->assertStatus(200);
        $response->assertSee('Sales Invoices', false);

        if ($sale) {
            $showResponse = $this->actingAs($this->admin)->get(route('sales.show', $sale->id));
            $showResponse->assertStatus(200);
            $showResponse->assertSee($sale->invoice_no, false);
        }
    }

    public function test_expenses_and_reports(): void
    {
        // 1. Expenses
        $expResponse = $this->actingAs($this->admin)->get(route('expenses.index'));
        $expResponse->assertStatus(200);
        $expResponse->assertSee('Daily Expenses', false);
        $expResponse->assertSee('Petty Cash', false);

        // 2. Executive Reports & COGS Margin
        $reportResponse = $this->actingAs($this->admin)->get(route('reports.index'));
        $reportResponse->assertStatus(200);
        $reportResponse->assertSee('Profitability Analytics', false);
        $reportResponse->assertSee('Net Sales Revenue', false);
        $reportResponse->assertSee('Cost of Goods', false);
    }

    public function test_branches_and_counters(): void
    {
        $response = $this->actingAs($this->admin)->get(route('branches.index'));
        $response->assertStatus(200);
        $response->assertSee('Multi-Branch', false);
        $response->assertSee('POS Register Counters', false);
    }
}
