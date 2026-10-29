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
        // 1. Manager PIN for override verifications
        if (!Schema::hasColumn('users', 'manager_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('manager_pin', 60)->nullable()->after('password');
            });
        }

        // 2. Customer Payments & Credit Ledger Settlements
        if (!Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_no')->unique(); // e.g. CPAY-20261020-0001
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'cheque'])->default('cash');
                $table->string('reference_no')->nullable();
                $table->date('payment_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        // 3. Held / Parked Carts
        if (!Schema::hasTable('held_sales')) {
            Schema::create('held_sales', function (Blueprint $table) {
                $table->id();
                $table->string('reference'); // e.g. HOLD-001 or Customer Name
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('register_id')->nullable()->constrained('registers')->nullOnDelete();
                $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('customer_name')->nullable();
                $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
                $table->decimal('discount_amount', 12, 2)->default(0.00);
                $table->decimal('tax_rate', 5, 2)->default(0.00);
                $table->decimal('subtotal', 12, 2)->default(0.00);
                $table->decimal('grand_total', 12, 2)->default(0.00);
                $table->string('note')->nullable();
                $table->enum('status', ['held', 'resumed', 'cancelled'])->default('held');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('held_sale_items')) {
            Schema::create('held_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('held_sale_id')->constrained('held_sales')->cascadeOnDelete();
                $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('subtotal', 12, 2);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('held_sale_items');
        Schema::dropIfExists('held_sales');
        Schema::dropIfExists('customer_payments');

        if (Schema::hasColumn('users', 'manager_pin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('manager_pin');
            });
        }
    }
};

