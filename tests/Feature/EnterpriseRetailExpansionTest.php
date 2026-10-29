<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Quotation;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockTake;
use App\Models\User;
use App\Services\LankaQrService;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseRetailExpansionTest extends TestCase
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

    private function openShift(): CashierShift
    {
        return CashierShift::create([
            'user_id' => $this->cashier->id,
            'branch_id' => $this->branch->id,
            'register_id' => $this->register->id,
            'opened_at' => now(),
            'opening_balance' => 5000.00,
            'status' => 'open',
        ]);
    }

    /** 1. Loyalty Points Redemption at POS Checkout */
    public function test_loyalty_points_redemption_at_checkout(): void
    {
        $this->openShift();
        $variation = ProductVariation::first();

        // Customer with 500 loyalty points
        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'name' => 'Dilshan Perera Unique',
            'phone' => '0779998877',
            'loyalty_points' => 500,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'pricing_mode' => 'retail',
            'redeem_points' => 300,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_rate' => 0,
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => 1,
                    'unit_price' => 1000.00,
                    'is_exchange' => false,
                ]
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount' => 700.00, // 1000 - 300 points = 700 due
                    'tender_amount' => 700.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $payload);
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales', [
            'customer_id' => $customer->id,
            'loyalty_points_redeemed' => 300,
            'loyalty_discount_amount' => 300.00,
            'grand_total' => 700.00,
        ]);

        // Customer points should be reduced to 200 (plus 7 earned from net 700 spend)
        $customer->refresh();
        $this->assertEquals(200 + 7, $customer->loyalty_points);
    }

    /** 2. Wholesale vs Retail Pricing Toggle */
    public function test_wholesale_pricing_mode_checkout(): void
    {
        $this->openShift();
        $variation = ProductVariation::first();
        $variation->update([
            'selling_price' => 2500.00,
            'wholesale_price' => 1900.00,
        ]);

        $payload = [
            'pricing_mode' => 'wholesale',
            'items' => [
                [
                    'variation_id' => $variation->id,
                    'quantity' => 2,
                    'unit_price' => 1900.00,
                    'is_exchange' => false,
                ]
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount' => 3800.00,
                    'tender_amount' => 4000.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $payload);
        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales', [
            'pricing_mode' => 'wholesale',
            'subtotal' => 3800.00,
            'grand_total' => 3800.00,
        ]);
    }

    /** 3. Item Exchange / Swap in Same Transaction */
    public function test_exchange_and_item_swap_in_same_transaction(): void
    {
        $this->openShift();
        $variations = ProductVariation::take(2)->get();
        $newPurchasedVar = $variations[0];
        $returnedVar = $variations[1];

        // Ensure baseline stock
        $initialReturnStock = Stock::where('branch_id', $this->branch->id)
            ->where('product_variation_id', $returnedVar->id)
            ->sum('quantity');

        $payload = [
            'pricing_mode' => 'retail',
            'items' => [
                [
                    'variation_id' => $newPurchasedVar->id,
                    'quantity' => 1,
                    'unit_price' => 4500.00,
                    'is_exchange' => false,
                ],
                [
                    'variation_id' => $returnedVar->id,
                    'quantity' => 1,
                    'unit_price' => 2000.00,
                    'is_exchange' => true,
                    'exchange_reason' => 'Wrong size swap',
                ]
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount' => 2500.00, // 4500 - 2000 = 2500
                    'tender_amount' => 2500.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->cashier)->postJson(route('pos.checkout'), $payload);
        $response->assertStatus(200)->assertJson(['success' => true]);

        // Assert sale item exchange flag
        $this->assertDatabaseHas('sale_items', [
            'product_variation_id' => $returnedVar->id,
            'is_exchange' => true,
            'exchange_reason' => 'Wrong size swap',
        ]);

        // Returned item stock should have incremented by 1
        $updatedReturnStock = Stock::where('branch_id', $this->branch->id)
            ->where('product_variation_id', $returnedVar->id)
            ->sum('quantity');
        $this->assertEquals($initialReturnStock + 1, $updatedReturnStock);
    }

    /** 4. LankaQR Service & QR Endpoint */
    public function test_lanka_qr_generation(): void
    {
        $payload = LankaQrService::generatePayload('PSI COLOMBO', 'Colombo', 3500.50, 'INV-TEST-001');

        $this->assertStringStartsWith('000201010212', $payload);
        $this->assertStringContainsString('5303144', $payload); // Currency LKR (144)
        $this->assertStringContainsString('3500.50', $payload);
        $this->assertStringContainsString('PSI COLOMBO', $payload);
        $this->assertStringContainsString('6304', $payload); // CRC tag

        // Test POS endpoint
        $response = $this->actingAs($this->cashier)->getJson(route('pos.lankaqr', ['amount' => 1500, 'reference' => 'POS-001']));
        $response->assertStatus(200)->assertJson([
            'success' => true,
            'amount' => 1500,
        ]);
    }

    /** 5. Quotation Lifecycle & 1-Click Conversion to Sale */
    public function test_quotation_lifecycle_and_conversion_to_sale(): void
    {
        $variation = ProductVariation::first();
        Stock::updateOrCreate(
            ['branch_id' => $this->branch->id, 'product_variation_id' => $variation->id],
            ['quantity' => 10, 'reorder_level' => 2]
        );

        // 1. Create Quotation
        $quotationData = [
            'branch_id' => $this->branch->id,
            'customer_name' => 'Apex B2B Partners',
            'customer_phone' => '0112345678',
            'pricing_mode' => 'wholesale',
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'tax_rate' => 0,
            'items' => [
                [
                    'product_variation_id' => $variation->id,
                    'quantity' => 4,
                    'unit_price' => 1200.00,
                ]
            ]
        ];

        $createResponse = $this->actingAs($this->admin)->post(route('quotations.store'), $quotationData);
        $createResponse->assertRedirect();

        $quotation = Quotation::where('customer_name', 'Apex B2B Partners')->first();
        $this->assertNotNull($quotation);
        $this->assertEquals('draft', $quotation->status);
        $this->assertEquals(4800.00, $quotation->grand_total);

        // 2. View details and printable
        $this->actingAs($this->admin)->get(route('quotations.show', $quotation->id))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('quotations.print', $quotation->id))->assertStatus(200);

        // 3. Update status to sent
        $this->actingAs($this->admin)->postJson(route('quotations.status', $quotation->id), ['status' => 'sent'])
            ->assertStatus(200);
        $this->assertEquals('sent', $quotation->fresh()->status);

        // 4. Convert Quotation to Real POS Sale
        $convertResponse = $this->actingAs($this->admin)->post(route('quotations.convert', $quotation->id));
        $convertResponse->assertRedirect();

        $quotation->refresh();
        $this->assertEquals('converted', $quotation->status);

        // Verify sale was created
        $this->assertDatabaseHas('sales', [
            'quotation_id' => $quotation->id,
            'grand_total' => 4800.00,
        ]);

        // Verify stock deducted: 10 - 4 = 6
        $remainingStock = Stock::where('branch_id', $this->branch->id)
            ->where('product_variation_id', $variation->id)
            ->sum('quantity');
        $this->assertEquals(6, $remainingStock);
    }

    /** 6. Physical Stock Audit Cycle Count & Reconciliation */
    public function test_physical_stock_take_and_reconciliation(): void
    {
        $variation = ProductVariation::first();
        Stock::updateOrCreate(
            ['branch_id' => $this->branch->id, 'product_variation_id' => $variation->id],
            ['quantity' => 20, 'reorder_level' => 5]
        );

        // 1. Initialize Stock Take
        $initResponse = $this->actingAs($this->admin)->post(route('inventory.stock_takes.store'), [
            'branch_id' => $this->branch->id,
            'notes' => 'End of Month Audit Session',
        ]);
        $initResponse->assertRedirect();

        $stockTake = StockTake::where('notes', 'End of Month Audit Session')->first();
        $this->assertNotNull($stockTake);
        $this->assertEquals('in_progress', $stockTake->status);

        // Snapshot should show system_qty = 20
        $takeItem = $stockTake->items()->where('product_variation_id', $variation->id)->first();
        $this->assertNotNull($takeItem);
        $this->assertEquals(20, $takeItem->system_qty);

        // 2. Barcode Scanner Scan: Counted 18 items (Variance -2)
        $scanResponse = $this->actingAs($this->admin)->postJson(route('inventory.stock_takes.scan', $stockTake->id), [
            'barcode' => $variation->sku,
            'quantity' => 18,
            'set_exact' => true,
        ]);
        $scanResponse->assertStatus(200)->assertJson(['success' => true]);

        $takeItem->refresh();
        $this->assertEquals(18, $takeItem->physical_qty);
        $this->assertEquals(-2, $takeItem->variance_qty);

        // 3. Print audit discrepancy report
        $this->actingAs($this->admin)->get(route('inventory.stock_takes.print', $stockTake->id))->assertStatus(200);

        // 4. Finalize & Reconcile Physical Stock
        $reconcileResponse = $this->actingAs($this->admin)->post(route('inventory.stock_takes.complete', $stockTake->id));
        $reconcileResponse->assertRedirect();

        $stockTake->refresh();
        $this->assertEquals('completed', $stockTake->status);

        // Inventory should now reflect physical count: 18
        $currentStock = Stock::where('branch_id', $this->branch->id)
            ->where('product_variation_id', $variation->id)
            ->sum('quantity');
        $this->assertEquals(18, $currentStock);
    }

    /** 7. Dual Screen Customer Facing Display Route */
    public function test_customer_facing_display_page(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('pos.customer-display'));
        $response->assertStatus(200);
        $response->assertSee('Customer Display');
    }

    /** 8. WhatsApp E-Receipt Generation */
    public function test_whatsapp_digital_receipt(): void
    {
        $this->openShift();
        $variation = ProductVariation::first();
        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'name' => 'Kasun Chamara Unique',
            'phone' => '0719876543',
        ]);

        $sale = Sale::create([
            'invoice_no' => 'INV-TEST-WA-001',
            'branch_id' => $this->branch->id,
            'register_id' => $this->register->id,
            'customer_id' => $customer->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 2000.00,
            'grand_total' => 2000.00,
            'paid_amount' => 2000.00,
            'change_amount' => 0.00,
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($this->cashier)->getJson(route('pos.receipt.whatsapp', $sale->id));
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'phone', 'text', 'whatsapp_url']);
        $this->assertStringContainsString('wa.me/94719876543', $response->json('whatsapp_url'));
    }
}

