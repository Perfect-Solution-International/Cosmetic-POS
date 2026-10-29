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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0.00)->after('subtotal');
            $table->decimal('discount_amount', 12, 2)->default(0.00)->after('discount_percent');
            $table->string('payment_terms')->nullable()->default('Net 30')->after('shipping_cost');
            $table->date('payment_due_date')->nullable()->after('payment_terms');
            $table->text('delivery_instructions')->nullable()->after('notes');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->integer('free_quantity')->default(0)->after('quantity_ordered');
            $table->decimal('discount_percent', 5, 2)->default(0.00)->after('unit_cost');
            $table->decimal('tax_percent', 5, 2)->default(0.00)->after('discount_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['free_quantity', 'discount_percent', 'tax_percent']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_amount', 'payment_terms', 'payment_due_date', 'delivery_instructions']);
        });
    }
};

