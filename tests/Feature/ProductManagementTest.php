<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Database\Seeders\SystemDataSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;
    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(SystemDataSeeder::class);

        $this->admin = User::where('username', 'admin')->first();
        $this->category = Category::first();
        $this->brand = Brand::first();
    }

    public function test_product_edit_screen_renders_successfully(): void
    {
        $product = Product::create([
            'name' => 'Hydrating Lip Gloss',
            'code' => 'PSI-LIP-TEST',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand ? $this->brand->id : null,
            'type' => 'simple',
            'has_expiry' => true,
            'status' => 'active',
        ]);

        ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Hydrating Lip Gloss',
            'sku' => 'LIP-GLOSS-01',
            'barcode' => '7891234567890',
            'purchase_cost' => 1500.00,
            'selling_price' => 2800.00,
            'wholesale_price' => 2400.00,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('products.edit', $product->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Product: Hydrating Lip Gloss', false);
        $response->assertSee('2800', false);
        $response->assertSee('LIP-GLOSS-01', false);
    }

    public function test_can_update_simple_product_pricing_and_details(): void
    {
        $product = Product::create([
            'name' => 'Original Hair Serum',
            'code' => 'PSI-SERUM-01',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand ? $this->brand->id : null,
            'type' => 'simple',
            'has_expiry' => false,
            'status' => 'active',
        ]);

        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Original Hair Serum',
            'sku' => 'SERUM-ORIG-01',
            'barcode' => '111222333444',
            'purchase_cost' => 1000.00,
            'selling_price' => 2000.00,
            'wholesale_price' => 1800.00,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('products.update', $product->id), [
            'name' => 'Updated Premium Hair Serum',
            'code' => 'PSI-SERUM-01',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand ? $this->brand->id : null,
            'type' => 'simple',
            'status' => 'active',
            'description' => 'Updated formulation with Argan oil',
            'single_sku' => 'SERUM-ORIG-01',
            'single_barcode' => '999888777666',
            'single_purchase_cost' => 1250.00,
            'single_selling_price' => 3200.00,
            'single_wholesale_price' => 2900.00,
            'single_reorder_level' => 10,
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Premium Hair Serum',
        ]);

        $this->assertDatabaseHas('product_variations', [
            'id' => $variation->id,
            'barcode' => '999888777666',
            'purchase_cost' => 1250.00,
            'selling_price' => 3200.00,
            'wholesale_price' => 2900.00,
            'reorder_level' => 10,
        ]);
    }

    public function test_can_update_variable_product_and_variations(): void
    {
        $product = Product::create([
            'name' => 'Velvet Liquid Lipstick',
            'code' => 'PSI-LIP-VAR',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand ? $this->brand->id : null,
            'type' => 'variable',
            'status' => 'active',
        ]);

        $var1 = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Ruby Red',
            'sku' => 'LIP-RUBY',
            'barcode' => '1001',
            'purchase_cost' => 500.00,
            'selling_price' => 1200.00,
            'is_active' => true,
        ]);

        $var2 = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Nude Pink',
            'sku' => 'LIP-PINK',
            'barcode' => '1002',
            'purchase_cost' => 500.00,
            'selling_price' => 1200.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('products.update', $product->id), [
            'name' => 'Velvet Liquid Lipstick Pro',
            'code' => 'PSI-LIP-VAR',
            'category_id' => $this->category->id,
            'type' => 'variable',
            'status' => 'active',
            'variations' => [
                [
                    'id' => $var1->id,
                    'name' => 'Ruby Red Classic',
                    'sku' => 'LIP-RUBY-NEW',
                    'barcode' => '1001-NEW',
                    'purchase_cost' => 600.00,
                    'selling_price' => 1500.00,
                    'wholesale_price' => 1350.00,
                    'is_active' => 1,
                ],
                // Add a brand new third variation
                [
                    'name' => 'Coral Blaze',
                    'sku' => 'LIP-CORAL',
                    'barcode' => '1003',
                    'purchase_cost' => 600.00,
                    'selling_price' => 1500.00,
                    'wholesale_price' => 1350.00,
                    'is_active' => 1,
                ]
            ]
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Velvet Liquid Lipstick Pro',
        ]);

        $this->assertDatabaseHas('product_variations', [
            'id' => $var1->id,
            'name' => 'Ruby Red Classic',
            'sku' => 'LIP-RUBY-NEW',
            'selling_price' => 1500.00,
        ]);

        $this->assertDatabaseHas('product_variations', [
            'product_id' => $product->id,
            'name' => 'Coral Blaze',
            'sku' => 'LIP-CORAL',
            'selling_price' => 1500.00,
        ]);
    }

    public function test_quick_price_update_endpoint(): void
    {
        $product = Product::create([
            'name' => 'Quick Price Test Item',
            'code' => 'PSI-QP-01',
            'category_id' => $this->category->id,
            'type' => 'simple',
            'status' => 'active',
        ]);

        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Quick Price Test Item',
            'sku' => 'QP-01',
            'purchase_cost' => 500.00,
            'selling_price' => 1000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('products.quick-price', $product->id), [
            'variations' => [
                [
                    'id' => $variation->id,
                    'selling_price' => 1450.00,
                    'purchase_cost' => 650.00,
                    'wholesale_price' => 1300.00,
                    'barcode' => '555666777',
                ]
            ]
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_variations', [
            'id' => $variation->id,
            'selling_price' => 1450.00,
            'purchase_cost' => 650.00,
            'wholesale_price' => 1300.00,
            'barcode' => '555666777',
        ]);
    }

    public function test_toggle_product_status(): void
    {
        $product = Product::create([
            'name' => 'Status Toggle Item',
            'code' => 'PSI-TOG-01',
            'category_id' => $this->category->id,
            'type' => 'simple',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('products.toggle-status', $product->id));
        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'inactive']);

        $response2 = $this->actingAs($this->admin)->patch(route('products.toggle-status', $product->id));
        $response2->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'status' => 'active']);
    }

    public function test_can_permanently_delete_unused_product(): void
    {
        $product = Product::create([
            'name' => 'Unused Product To Delete',
            'code' => 'PSI-DEL-01',
            'category_id' => $this->category->id,
            'type' => 'simple',
            'status' => 'active',
        ]);

        ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Unused Product',
            'sku' => 'DEL-01',
            'selling_price' => 100.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('products.destroy', $product->id));
        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
