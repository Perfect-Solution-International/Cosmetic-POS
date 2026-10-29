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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('exp_date')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->timestamps();

            $table->index(['branch_id', 'product_variation_id']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no')->unique(); // e.g. TRF-2026-0001
            $table->foreignId('from_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->enum('status', ['pending', 'approved', 'in_transit', 'received', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->integer('quantity_requested');
            $table->integer('quantity_sent')->default(0);
            $table->integer('quantity_received')->default(0);
            $table->text('discrepancy_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_no')->unique(); // e.g. ADJ-2026-0001
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_variation_id')->constrained('product_variations')->cascadeOnDelete();
            $table->string('batch_number')->nullable();
            $table->enum('type', ['addition', 'subtraction', 'damaged', 'expired', 'audit_discrepancy'])->default('subtraction');
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->foreignId('adjusted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stocks');
    }
};

