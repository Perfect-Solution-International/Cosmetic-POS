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
        // 1. Add serial tracking & warranty fields to product_variations
        Schema::table('product_variations', function (Blueprint $table) {
            $table->boolean('track_serial')->default(false)->after('reorder_level');
            $table->integer('warranty_months')->default(0)->after('track_serial');
        });

        // 2. Add serial numbers tracking to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->text('serial_numbers')->nullable()->after('subtotal'); // JSON array or comma separated
        });

        // 3. Create product_serials table for unique IMEI / Serial # tracking
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('serial_number')->unique(); // Unique Serial / IMEI
            $table->enum('status', ['in_stock', 'sold', 'returned', 'defective', 'transferred'])->default('in_stock');
            $table->decimal('purchase_cost', 12, 2)->default(0.00);
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->integer('warranty_months')->default(0);
            $table->date('warranty_expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status'], 'serials_branch_status_idx');
            $table->index('serial_number', 'serials_number_idx');
            $table->index('warranty_expires_at', 'serials_warranty_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serials');

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('serial_numbers');
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn(['track_serial', 'warranty_months']);
        });
    }
};

