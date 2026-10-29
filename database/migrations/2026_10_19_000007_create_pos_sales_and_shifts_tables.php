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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('register_id')->constrained('registers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('opening_float', 12, 2)->default(0.00);
            $table->decimal('cash_sales', 12, 2)->default(0.00);
            $table->decimal('card_sales', 12, 2)->default(0.00);
            $table->decimal('other_sales', 12, 2)->default(0.00);
            $table->decimal('expected_cash', 12, 2)->default(0.00);
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('cash_discrepancy', 12, 2)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique(); // e.g. INV-2026-0001
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('register_id')->constrained('registers')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_rate', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('change_amount', 12, 2)->default(0.00);
            $table->enum('payment_status', ['paid', 'partial', 'refunded', 'voided'])->default('paid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->integer('quantity');
            $table->decimal('purchase_cost', 12, 2)->default(0.00); // Historical COGS for gross profit calculation
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'cheque', 'loyalty_points', 'credit'])->default('cash');
            $table->decimal('amount', 12, 2);
            $table->decimal('tender_amount', 12, 2)->nullable();
            $table->decimal('change_amount', 12, 2)->default(0.00);
            $table->string('card_ref')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique(); // e.g. RET-2026-0001
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_refund_amount', 12, 2)->default(0.00);
            $table->text('reason')->nullable();
            $table->enum('action', ['restock', 'damaged'])->default('restock');
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('refund_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cashier_shifts');
    }
};

