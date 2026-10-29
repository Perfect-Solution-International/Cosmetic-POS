<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand sales table for Loyalty Redemption, Wholesale Pricing & Quotation conversions
        Schema::table('sales', function (Blueprint $table) {
            $table->integer('loyalty_points_redeemed')->default(0)->after('change_amount');
            $table->decimal('loyalty_discount_amount', 12, 2)->default(0.00)->after('loyalty_points_redeemed');
            $table->string('pricing_mode', 20)->default('retail')->after('loyalty_discount_amount'); // retail, wholesale
            $table->unsignedBigInteger('quotation_id')->nullable()->after('pricing_mode');
        });

        // 2. Expand sale_items table for Item Exchange / Swap support
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_exchange')->default(false)->after('subtotal');
            $table->string('exchange_reason')->nullable()->after('is_exchange');
        });

        // 3. Expand customers for Wholesale Pricing Tier
        Schema::table('customers', function (Blueprint $table) {
            $table->string('pricing_tier', 20)->default('retail')->after('loyalty_points'); // retail, wholesale
        });

        // 4. Update payment methods to support 'qr' and 'loyalty_points'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sale_payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'bank_transfer', 'cheque', 'loyalty_points', 'credit', 'qr') DEFAULT 'cash'");
        }

        // 5. Quotations / Proforma Invoices Table
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no')->unique(); // e.g. QT-20261021-0001
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->date('date');
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('draft'); // draft, sent, accepted, rejected, converted
            $table->string('pricing_mode', 20)->default('retail'); // retail, wholesale
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('grand_total', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->unsignedBigInteger('converted_sale_id')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('quotation_no');
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // 6. Physical Stock Takes / Cycle Count Audits
        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique(); // e.g. ST-20261021-0001
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('in_progress'); // draft, in_progress, completed, cancelled
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_system_qty')->default(0);
            $table->integer('total_physical_qty')->default(0);
            $table->integer('total_variance_qty')->default(0);
            $table->decimal('total_variance_cost', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index('reference_no');
        });

        Schema::create('stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_take_id')->constrained('stock_takes')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->integer('system_qty')->default(0);
            $table->integer('physical_qty')->default(0);
            $table->integer('variance_qty')->default(0); // physical_qty - system_qty
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('variance_cost', 12, 2)->default(0.00); // variance_qty * unit_cost
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->unique(['stock_take_id', 'product_variation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_items');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('pricing_tier');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_exchange', 'exchange_reason']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_redeemed', 'loyalty_discount_amount', 'pricing_mode', 'quotation_id']);
        });
    }
};

