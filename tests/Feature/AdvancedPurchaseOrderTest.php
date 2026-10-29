<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Supplier $supplier;
    protected Branch $branch;
    protected ProductVariation $variation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);

        $this->admin = User::where('role', 'admin')->first();

        $this->branch = Branch::create([
            'name' => 'Colombo Flagship Store',
            'code' => 'CMB-01',
            'status' => 'active',
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Unilever Sri Lanka',
            'code' => 'SUP-UL-01',
            'status' => 'active',
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Personal Care',
            'slug' => 'personal-care',
            'status' => 'active',
        ]);

        $product = Product::create([
            'name' => 'Lux Velvet Touch Soap',
            'code' => 'PRD-LUX-01',
            'category_id' => $category->id,
            'brand' => 'Lux',
            'unit' => 'pcs',
            'has_variations' => false,
            'status' => 'active',
        ]);

        $this->variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => '100g Bar',
            'sku' => 'LUX-100G',
            'barcode' => '4790001112223',
            'cost_price' => 120.00,
            'selling_price' => 150.00,
            'reorder_level' => 20,
            'is_active' => true,
        ]);
    }

    public function test_can_create_advanced_po_with_commercial_terms_and_free_issues(): void
    {
        $response = $this->actingAs($this->admin)->post(route('procurement.po.store'), [
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'order_date' => date('Y-m-d'),
            'expected_delivery_date' => date('Y-m-d', strtotime('+3 days')),
            'payment_terms' => 'Net 30 Days',
            'payment_due_date' => date('Y-m-d', strtotime('+30 days')),
            'discount_percent' => 10, // 10% Trade Discount
            'tax_amount' => 50,
            'shipping_cost' => 100,
            'delivery_instructions' => 'Deliver to basement warehouse.',
            'status' => 'approved',
            'items' => [
                [
                    'product_variation_id' => $this->variation->id,
                    'quantity' => 100,
                    'free_quantity' => 10, // Buy 100 Get 10 Free promotional deal
                    'unit_cost' => 120.00,
                    'discount_percent' => 0,
                ],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'payment_terms' => 'Net 30 Days',
            'status' => 'approved',
        ]);

        $po = PurchaseOrder::where('supplier_id', $this->supplier->id)->first();
        $this->assertNotNull($po);
        $response->assertRedirect(route('procurement.po.show', $po->id));

        // Subtotal = 100 * 120 = 12000
        // Discount 10% = 1200
        // Tax = 50, Shipping = 100
        // Total = (12000 - 1200) + 50 + 100 = 10950
        $this->assertEquals(12000.00, (float) $po->subtotal);
        $this->assertEquals(1200.00, (float) $po->discount_amount);
        $this->assertEquals(10950.00, (float) $po->total_amount);

        // Verify items
        $this->assertEquals(100, $po->total_ordered_quantity);
        $this->assertEquals(10, $po->total_free_quantity);
        $this->assertEquals(0, $po->total_received_quantity);
        $this->assertEquals(100, $po->total_backorder_quantity);
    }

    public function test_can_view_po_details_and_print_view(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-0001',
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'order_date' => date('Y-m-d'),
            'subtotal' => 12000.00,
            'total_amount' => 12000.00,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $po->items()->create([
            'product_variation_id' => $this->variation->id,
            'quantity_ordered' => 100,
            'free_quantity' => 10,
            'quantity_received' => 0,
            'unit_cost' => 120.00,
            'subtotal' => 12000.00,
        ]);

        // Details view
        $showResponse = $this->actingAs($this->admin)->get(route('procurement.po.show', $po->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('PO-TEST-0001');
        $showResponse->assertSee('Unilever Sri Lanka');
        $showResponse->assertSee('Lux Velvet Touch Soap');

        // Print view
        $printResponse = $this->actingAs($this->admin)->get(route('procurement.po.print', $po->id));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('PURCHASE ORDER', false);
        $printResponse->assertSee('PO-TEST-0001');
        $printResponse->assertSee('Authorized Manager');
    }

    public function test_po_status_workflow_transitions(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-STATUS',
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'order_date' => date('Y-m-d'),
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        // Submit for approval
        $this->actingAs($this->admin)->post(route('procurement.po.status', $po->id), ['status' => 'submitted']);
        $po->refresh();
        $this->assertEquals('submitted', $po->status);

        // Approve
        $this->actingAs($this->admin)->post(route('procurement.po.status', $po->id), ['status' => 'approved']);
        $po->refresh();
        $this->assertEquals('approved', $po->status);

        // Cancel
        $this->actingAs($this->admin)->post(route('procurement.po.status', $po->id), ['status' => 'cancelled']);
        $po->refresh();
        $this->assertEquals('cancelled', $po->status);
    }

    public function test_partial_receiving_and_backorders_tracking(): void
    {
        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-PARTIAL',
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'order_date' => date('Y-m-d'),
            'subtotal' => 12000.00,
            'total_amount' => 12000.00,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $item = $po->items()->create([
            'product_variation_id' => $this->variation->id,
            'quantity_ordered' => 100,
            'quantity_received' => 0,
            'unit_cost' => 120.00,
            'subtotal' => 12000.00,
        ]);

        // 1st Delivery: Supplier delivers 60 items only
        $this->actingAs($this->admin)->post(route('procurement.grn.store'), [
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'purchase_order_id' => $po->id,
            'received_date' => date('Y-m-d'),
            'items' => [
                [
                    'product_variation_id' => $this->variation->id,
                    'quantity' => 60,
                    'unit_cost' => 120.00,
                ],
            ],
        ]);

        $po->refresh();
        $item->refresh();

        $this->assertEquals(60, $item->quantity_received);
        $this->assertEquals(40, $item->backorder_quantity);
        $this->assertEquals('partially_received', $po->status);

        // 2nd Delivery: Supplier delivers remaining 40 items
        $this->actingAs($this->admin)->post(route('procurement.grn.store'), [
            'supplier_id' => $this->supplier->id,
            'branch_id' => $this->branch->id,
            'purchase_order_id' => $po->id,
            'received_date' => date('Y-m-d'),
            'items' => [
                [
                    'product_variation_id' => $this->variation->id,
                    'quantity' => 40,
                    'unit_cost' => 120.00,
                ],
            ],
        ]);

        $po->refresh();
        $item->refresh();

        $this->assertEquals(100, $item->quantity_received);
        $this->assertEquals(0, $item->backorder_quantity);
        $this->assertEquals('completed', $po->status);
    }
}
