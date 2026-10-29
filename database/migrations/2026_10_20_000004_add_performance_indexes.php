<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Sales Performance Indexes
        Schema::table('sales', function (Blueprint $table) {
            $table->index('created_at', 'sales_created_at_idx');
            $table->index(['branch_id', 'created_at'], 'sales_branch_created_idx');
            $table->index('payment_status', 'sales_payment_status_idx');
        });

        // 2. Sale Items Performance Indexes
        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['sale_id', 'product_variation_id'], 'sale_items_sale_variation_idx');
        });

        // 3. Cashier Shifts Performance Indexes
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'shifts_branch_status_idx');
            $table->index(['user_id', 'status'], 'shifts_user_status_idx');
        });

        // 4. Stocks Performance Indexes
        Schema::table('stocks', function (Blueprint $table) {
            $table->index(['branch_id', 'product_variation_id'], 'stocks_branch_variation_idx');
        });

        // 5. Products & Variations Performance Indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('status', 'products_status_idx');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->index('is_active', 'variations_is_active_idx');
        });

        // 6. Held Sales Performance Indexes
        Schema::table('held_sales', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'held_sales_branch_status_idx');
        });

        // 7. Purchase Orders & GRN Indexes
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index('status', 'po_status_idx');
            $table->index('order_date', 'po_order_date_idx');
        });

        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->index('payment_status', 'grn_payment_status_idx');
            $table->index('received_date', 'grn_received_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_created_at_idx');
            $table->dropIndex('sales_branch_created_idx');
            $table->dropIndex('sales_payment_status_idx');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_sale_variation_idx');
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_branch_status_idx');
            $table->dropIndex('shifts_user_status_idx');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex('stocks_branch_variation_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_idx');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex('variations_is_active_idx');
        });

        Schema::table('held_sales', function (Blueprint $table) {
            $table->dropIndex('held_sales_branch_status_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('po_status_idx');
            $table->dropIndex('po_order_date_idx');
        });

        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropIndex('grn_payment_status_idx');
            $table->dropIndex('grn_received_date_idx');
        });
    }
};

