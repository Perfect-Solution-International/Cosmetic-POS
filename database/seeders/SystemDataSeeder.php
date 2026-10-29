<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Branches & Warehouses
        $colombo = Branch::updateOrCreate(['code' => 'BR-COL-01'], [
            'name' => 'Colombo Flagship Store',
            'phone' => '+94 11 234 5678',
            'email' => 'colombo@psipos.com',
            'address' => 'No 120, Galle Road, Colombo 03',
            'city' => 'Colombo',
            'type' => 'retail_store',
            'receipt_header' => 'Psi One Global POS - Colombo Flagship',
            'receipt_footer' => 'Thank you for shopping with us! Returns within 7 days.',
            'status' => 'active',
        ]);

        $kandy = Branch::updateOrCreate(['code' => 'BR-KDY-01'], [
            'name' => 'Kandy City Centre Branch',
            'phone' => '+94 81 223 4567',
            'email' => 'kandy@psipos.com',
            'address' => 'KCC Level 2, Kandy',
            'city' => 'Kandy',
            'type' => 'retail_store',
            'receipt_header' => 'Psi One Global POS - Kandy Branch',
            'receipt_footer' => 'Thank you for your visit!',
            'status' => 'active',
        ]);

        $warehouse = Branch::updateOrCreate(['code' => 'WH-COL-01'], [
            'name' => 'Colombo Central Warehouse',
            'phone' => '+94 11 445 6789',
            'email' => 'warehouse@psipos.com',
            'address' => 'Logistics Zone, Peliyagoda',
            'city' => 'Peliyagoda',
            'type' => 'central_warehouse',
            'status' => 'active',
        ]);

        // 2. Registers
        $reg1 = Register::updateOrCreate(['code' => 'REG-01-MAIN'], [
            'branch_id' => $colombo->id,
            'name' => 'Counter 01 - Main POS',
            'status' => 'open',
        ]);

        $reg2 = Register::updateOrCreate(['code' => 'REG-02-EXP'], [
            'branch_id' => $colombo->id,
            'name' => 'Counter 02 - Express Register',
            'status' => 'closed',
        ]);

        Register::updateOrCreate(['code' => 'REG-KDY-01'], [
            'branch_id' => $kandy->id,
            'name' => 'Counter 01 - Kandy Main',
            'status' => 'closed',
        ]);

        // 3. Roles
        $rolesData = [
            ['name' => 'Super Administrator', 'slug' => 'super_admin', 'description' => 'Business-wide full access'],
            ['name' => 'Head Office Manager', 'slug' => 'head_office_manager', 'description' => 'Central products & purchasing'],
            ['name' => 'Branch Manager', 'slug' => 'branch_manager', 'description' => 'Branch operations, staff, local approvals'],
            ['name' => 'Cashier', 'slug' => 'cashier', 'description' => 'POS billing, customer lookup, shift closing'],
            ['name' => 'Inventory Staff', 'slug' => 'inventory_staff', 'description' => 'Goods receiving, stock counting & transfers'],
            ['name' => 'Warehouse Manager', 'slug' => 'warehouse_manager', 'description' => 'Warehouse stock, dispatch & transfers'],
            ['name' => 'Accountant', 'slug' => 'accountant', 'description' => 'Supplier payments, expenses & financial reports'],
        ];

        foreach ($rolesData as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], $r);
        }

        // 4. Users
        $adminUser = User::updateOrCreate(['username' => 'admin'], [
            'name' => 'System Administrator',
            'email' => 'admin@psipos.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'branch_id' => $colombo->id,
            'phone' => '+94 77 123 4567',
            'status' => 'active',
        ]);

        $managerUser = User::updateOrCreate(['username' => 'manager'], [
            'name' => 'Store Manager - Colombo',
            'email' => 'manager@psipos.com',
            'password' => Hash::make('manager123'),
            'role' => 'manager',
            'branch_id' => $colombo->id,
            'phone' => '+94 77 234 5678',
            'status' => 'active',
        ]);

        $cashierUser = User::updateOrCreate(['username' => 'cashier'], [
            'name' => 'Front Desk Cashier',
            'email' => 'cashier@psipos.com',
            'password' => Hash::make('cashier123'),
            'role' => 'cashier',
            'branch_id' => $colombo->id,
            'phone' => '+94 77 345 6789',
            'status' => 'active',
        ]);

        $headofficeUser = User::updateOrCreate(['username' => 'headoffice'], [
            'name' => 'Head Office Procurement Manager',
            'email' => 'headoffice@psipos.com',
            'password' => Hash::make('head123'),
            'role' => 'head_office_manager',
            'branch_id' => $colombo->id,
            'phone' => '+94 77 456 7890',
            'status' => 'active',
        ]);

        $inventoryUser = User::updateOrCreate(['username' => 'inventory'], [
            'name' => 'Inventory Logistics Clerk',
            'email' => 'inventory@psipos.com',
            'password' => Hash::make('inv123'),
            'role' => 'inventory_staff',
            'branch_id' => $colombo->id,
            'phone' => '+94 77 567 8901',
            'status' => 'active',
        ]);

        $warehouseUser = User::updateOrCreate(['username' => 'warehouse'], [
            'name' => 'Peliyagoda Warehouse Manager',
            'email' => 'warehouse@psipos.com',
            'password' => Hash::make('wh123'),
            'role' => 'warehouse_manager',
            'branch_id' => $warehouse->id,
            'phone' => '+94 77 678 9012',
            'status' => 'active',
        ]);

        $accountantUser = User::updateOrCreate(['username' => 'accountant'], [
            'name' => 'Corporate Accountant',
            'email' => 'accountant@psipos.com',
            'password' => Hash::make('acc123'),
            'role' => 'accountant',
            'branch_id' => $colombo->id,
            'phone' => '+94 77 789 0123',
            'status' => 'active',
        ]);

        // 5. Categories
        $cosmetics = Category::updateOrCreate(['slug' => 'cosmetics'], [
            'name' => 'Cosmetics & Makeup',
            'icon' => 'fa-wand-magic-sparkles',
            'is_active' => true,
        ]);

        $skincare = Category::updateOrCreate(['slug' => 'skincare'], [
            'name' => 'Skincare & Sun Care',
            'icon' => 'fa-pump-soap',
            'is_active' => true,
        ]);

        $handbags = Category::updateOrCreate(['slug' => 'handbags'], [
            'name' => 'Handbags & Purses',
            'icon' => 'fa-bag-shopping',
            'is_active' => true,
        ]);

        $jewellery = Category::updateOrCreate(['slug' => 'jewellery-fashion'], [
            'name' => 'Fashion Accessories & Jewellery',
            'icon' => 'fa-gem',
            'is_active' => true,
        ]);

        $perfumes = Category::updateOrCreate(['slug' => 'perfumes'], [
            'name' => 'Perfumes & Fragrances',
            'icon' => 'fa-spray-can-sparkles',
            'is_active' => true,
        ]);

        // Subcategories
        Category::updateOrCreate(['slug' => 'lipsticks'], [
            'parent_id' => $cosmetics->id,
            'name' => 'Lipsticks & Lip Stains',
            'is_active' => true,
        ]);

        Category::updateOrCreate(['slug' => 'serums'], [
            'parent_id' => $skincare->id,
            'name' => 'Face Serums & Treatments',
            'is_active' => true,
        ]);

        // 6. Brands
        $mac = Brand::updateOrCreate(['slug' => 'mac'], ['name' => 'M·A·C Cosmetics', 'is_active' => true]);
        $loreal = Brand::updateOrCreate(['slug' => 'loreal'], ['name' => "L'Oréal Paris", 'is_active' => true]);
        $dior = Brand::updateOrCreate(['slug' => 'dior'], ['name' => 'Dior Luxury', 'is_active' => true]);
        $localGlam = Brand::updateOrCreate(['slug' => 'psi-signature'], ['name' => 'PSI Signature Retail', 'is_active' => true]);

        // 7. Dynamic Attributes & Values
        $colorAttr = Attribute::updateOrCreate(['slug' => 'color'], [
            'name' => 'Color / Shade',
            'type' => 'color',
            'is_variation' => true,
            'is_required' => true,
        ]);

        $sizeAttr = Attribute::updateOrCreate(['slug' => 'size'], [
            'name' => 'Size',
            'type' => 'dropdown',
            'is_variation' => true,
            'is_required' => true,
        ]);

        $finishAttr = Attribute::updateOrCreate(['slug' => 'finish'], [
            'name' => 'Finish Type',
            'type' => 'dropdown',
            'is_variation' => false,
            'is_required' => false,
        ]);

        $valRed = AttributeValue::updateOrCreate(['attribute_id' => $colorAttr->id, 'value' => 'Ruby Red'], ['color_code' => '#E51A2E']);
        $valPink = AttributeValue::updateOrCreate(['attribute_id' => $colorAttr->id, 'value' => 'Nude Rose'], ['color_code' => '#EAA39D']);
        $valBlack = AttributeValue::updateOrCreate(['attribute_id' => $colorAttr->id, 'value' => 'Jet Black'], ['color_code' => '#111111']);
        $valGold = AttributeValue::updateOrCreate(['attribute_id' => $colorAttr->id, 'value' => 'Champagne Gold'], ['color_code' => '#DFB15B']);

        $valSmall = AttributeValue::updateOrCreate(['attribute_id' => $sizeAttr->id, 'value' => 'Small']);
        $valMed = AttributeValue::updateOrCreate(['attribute_id' => $sizeAttr->id, 'value' => 'Medium']);
        $valLarge = AttributeValue::updateOrCreate(['attribute_id' => $sizeAttr->id, 'value' => 'Large']);

        // 8. Products & Variations
        // Product 1: Variable Lipstick
        $lipstick = Product::updateOrCreate(['code' => 'PRD-LIP-01'], [
            'name' => 'Velvet Matte Long-Wear Lipstick',
            'category_id' => $cosmetics->id,
            'brand_id' => $mac->id,
            'type' => 'variable',
            'has_expiry' => true,
            'description' => 'Ultra-pigmented rich matte lipstick with 16-hour hydration.',
            'status' => 'active',
        ]);

        $varLipRed = ProductVariation::updateOrCreate(['sku' => 'LIP-RUBY-RED'], [
            'product_id' => $lipstick->id,
            'name' => 'Velvet Matte - Ruby Red',
            'barcode' => '8901001001',
            'purchase_cost' => 12.50,
            'selling_price' => 24.00,
            'wholesale_price' => 18.00,
            'reorder_level' => 10,
            'is_active' => true,
        ]);
        $varLipRed->attributeValues()->syncWithoutDetaching([$valRed->id]);

        $varLipPink = ProductVariation::updateOrCreate(['sku' => 'LIP-NUDE-ROSE'], [
            'product_id' => $lipstick->id,
            'name' => 'Velvet Matte - Nude Rose',
            'barcode' => '8901001002',
            'purchase_cost' => 12.50,
            'selling_price' => 24.00,
            'wholesale_price' => 18.00,
            'reorder_level' => 8,
            'is_active' => true,
        ]);
        $varLipPink->attributeValues()->syncWithoutDetaching([$valPink->id]);

        // Product 2: Simple Skincare Serum (with batch & expiry)
        $serum = Product::updateOrCreate(['code' => 'PRD-SRM-01'], [
            'name' => 'Hyaluronic Glow Boost Serum 50ml',
            'category_id' => $skincare->id,
            'brand_id' => $loreal->id,
            'type' => 'simple',
            'has_expiry' => true,
            'description' => 'Pure 1.5% Hyaluronic Acid intense plumping facial serum.',
            'status' => 'active',
        ]);

        $varSerum = ProductVariation::updateOrCreate(['sku' => 'SRM-HYAL-50ML'], [
            'product_id' => $serum->id,
            'name' => 'Hyaluronic Glow Boost Serum 50ml',
            'barcode' => '8902002001',
            'purchase_cost' => 18.00,
            'selling_price' => 32.50,
            'wholesale_price' => 25.00,
            'reorder_level' => 12,
            'is_active' => true,
        ]);

        // Product 3: Variable Handbag
        $bag = Product::updateOrCreate(['code' => 'PRD-BAG-01'], [
            'name' => 'Signature Quilted Leather Crossbody Bag',
            'category_id' => $handbags->id,
            'brand_id' => $dior->id,
            'type' => 'variable',
            'has_expiry' => false,
            'description' => 'Italian crafted vegan leather with gold chain strap.',
            'status' => 'active',
        ]);

        $varBagBlackMed = ProductVariation::updateOrCreate(['sku' => 'BAG-QLT-BLK-M'], [
            'product_id' => $bag->id,
            'name' => 'Quilted Crossbody - Black / Medium',
            'barcode' => '8903003001',
            'purchase_cost' => 45.00,
            'selling_price' => 89.00,
            'wholesale_price' => 68.00,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
        $varBagBlackMed->attributeValues()->syncWithoutDetaching([$valBlack->id, $valMed->id]);

        $varBagRedMed = ProductVariation::updateOrCreate(['sku' => 'BAG-QLT-RED-M'], [
            'product_id' => $bag->id,
            'name' => 'Quilted Crossbody - Ruby Red / Medium',
            'barcode' => '8903003002',
            'purchase_cost' => 45.00,
            'selling_price' => 89.00,
            'wholesale_price' => 68.00,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
        $varBagRedMed->attributeValues()->syncWithoutDetaching([$valRed->id, $valMed->id]);

        // 9. Initial Stocks in Colombo Branch and Central Warehouse
        Stock::updateOrCreate([
            'branch_id' => $colombo->id,
            'product_variation_id' => $varLipRed->id,
            'batch_number' => 'BAT-LIP-2026A',
        ], [
            'exp_date' => '2028-06-30',
            'quantity' => 35,
            'reorder_level' => 10,
        ]);

        Stock::updateOrCreate([
            'branch_id' => $colombo->id,
            'product_variation_id' => $varLipPink->id,
            'batch_number' => 'BAT-LIP-2026B',
        ], [
            'exp_date' => '2028-06-30',
            'quantity' => 28,
            'reorder_level' => 8,
        ]);

        Stock::updateOrCreate([
            'branch_id' => $colombo->id,
            'product_variation_id' => $varSerum->id,
            'batch_number' => 'BAT-SRM-2026-01',
        ], [
            'exp_date' => '2027-12-31',
            'quantity' => 40,
            'reorder_level' => 12,
        ]);

        Stock::updateOrCreate([
            'branch_id' => $colombo->id,
            'product_variation_id' => $varBagBlackMed->id,
        ], [
            'quantity' => 15,
            'reorder_level' => 5,
        ]);

        Stock::updateOrCreate([
            'branch_id' => $colombo->id,
            'product_variation_id' => $varBagRedMed->id,
        ], [
            'quantity' => 10,
            'reorder_level' => 5,
        ]);

        // Warehouse Stock
        Stock::updateOrCreate([
            'branch_id' => $warehouse->id,
            'product_variation_id' => $varLipRed->id,
            'batch_number' => 'BAT-LIP-2026A',
        ], [
            'exp_date' => '2028-06-30',
            'quantity' => 200,
            'reorder_level' => 50,
        ]);

        // 10. Suppliers
        Supplier::updateOrCreate(['code' => 'SUP-BEAUTY-01'], [
            'name' => 'Luxe Cosmetics & Fragrance Imports Ltd',
            'company_name' => 'Luxe Imports International',
            'contact_person' => 'Dinesh Weerasinghe',
            'phone' => '+94 11 987 6543',
            'email' => 'sales@luxeimports.lk',
            'address' => '45 Logistics Park, Kelaniya',
            'opening_balance' => 0.00,
            'current_balance' => 4500.00,
            'payment_terms' => 'Net 30 Days',
            'status' => 'active',
        ]);

        Supplier::updateOrCreate(['code' => 'SUP-LEATHER-02'], [
            'name' => 'Milano Handbags & Accessories Corp',
            'company_name' => 'Milano Leather Goods Co',
            'contact_person' => 'Camilla Rossi',
            'phone' => '+94 11 876 5432',
            'email' => 'orders@milanogoods.com',
            'address' => 'Zone 4, Biyagama Export Zone',
            'opening_balance' => 0.00,
            'payment_terms' => 'COD / Advance',
            'status' => 'active',
        ]);

        // 11. Customer Groups & Customers
        $vipGroup = CustomerGroup::updateOrCreate(['name' => 'VIP Club Members'], ['discount_percentage' => 10.00]);
        $regularGroup = CustomerGroup::updateOrCreate(['name' => 'Regular Walk-in'], ['discount_percentage' => 0.00]);

        Customer::updateOrCreate(['phone' => '0770000000'], [
            'name' => 'Walk-in Cash Customer',
            'customer_group_id' => $regularGroup->id,
            'credit_limit' => 0.00,
            'loyalty_points' => 0,
        ]);

        Customer::updateOrCreate(['phone' => '0771234567'], [
            'name' => 'Sarah Alwis',
            'email' => 'sarah.alwis@gmail.com',
            'customer_group_id' => $vipGroup->id,
            'address' => 'Havelock City, Colombo 05',
            'credit_limit' => 50000.00,
            'loyalty_points' => 450,
        ]);

        // 12. Active Cashier Shift
        $shift = CashierShift::updateOrCreate([
            'branch_id' => $colombo->id,
            'register_id' => $reg1->id,
            'user_id' => $cashierUser->id,
            'status' => 'open',
        ], [
            'opening_float' => 15000.00,
            'cash_sales' => 38450.00,
            'card_sales' => 62100.00,
            'expected_cash' => 53450.00,
            'opened_at' => now()->startOfDay()->addHours(9),
        ]);

        // 13. Expense Categories
        ExpenseCategory::updateOrCreate(['name' => 'Store Utilities (Electricity & Water)']);
        ExpenseCategory::updateOrCreate(['name' => 'Staff Transport & Tea Expenses']);
        ExpenseCategory::updateOrCreate(['name' => 'Packaging Materials & Bags']);
        ExpenseCategory::updateOrCreate(['name' => 'Store Maintenance & Cleaning']);

        // 14. Subscription Packages (Monthly with Free Trial support)
        \App\Models\SubscriptionPackage::updateOrCreate(['code' => 'PKG-BASIC-3D'], [
            'name' => 'Basic Monthly Care (3-Day Trial)',
            'price' => 2500.00,
            'billing_cycle' => 'monthly',
            'default_trial_days' => 3,
            'description' => 'Entry level monthly service plan with 3-day full access free trial.',
            'features' => ['Regular Service Access', 'Direct Priority Support', 'Monthly Digital Billing'],
            'is_active' => true,
        ]);

        \App\Models\SubscriptionPackage::updateOrCreate(['code' => 'PKG-GOLD-7D'], [
            'name' => 'Gold VIP Membership (7-Day Trial)',
            'price' => 5500.00,
            'billing_cycle' => 'monthly',
            'default_trial_days' => 7,
            'description' => 'Premier VIP monthly membership including free perks and 7-day full access trial.',
            'features' => ['Unlimited Access', 'Dedicated Account Manager', 'Free Routine Checkup', 'Weekly Status SMS'],
            'is_active' => true,
        ]);

        \App\Models\SubscriptionPackage::updateOrCreate(['code' => 'PKG-PLATINUM'], [
            'name' => 'Platinum Enterprise Unlimited',
            'price' => 12000.00,
            'billing_cycle' => 'monthly',
            'default_trial_days' => 14,
            'description' => 'Comprehensive enterprise monthly package with 14-day evaluation trial.',
            'features' => ['24/7 Dedicated Line', 'On-site Support', 'Multi-user Coverage', 'Comprehensive Monthly Reports'],
            'is_active' => true,
        ]);
    }
}

