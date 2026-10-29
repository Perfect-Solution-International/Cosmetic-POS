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
        // 1. Subscription Packages / Plans
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Basic Monthly Care, Gold VIP Membership
            $table->string('code')->unique(); // e.g. PKG-BASIC, PKG-GOLD
            $table->decimal('price', 12, 2)->default(0.00); // Monthly fee
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, quarterly, yearly
            $table->integer('default_trial_days')->default(7); // 3, 7, 14, etc.
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_no')->unique(); // e.g. SUB-20261027-0001
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('subscription_packages')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('status', 25)->default('trial'); // trial, active, past_due, cancelled, expired
            $table->boolean('is_free_trial')->default(true);
            $table->integer('trial_days')->default(7); // 3, 7 or custom entered days
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->decimal('price', 12, 2)->default(0.00);
            $table->string('sales_channel', 25)->default('register'); // register, online
            $table->foreignId('sales_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('auto_renew')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Expand Sales table with Sales Channel & Subscription link
        Schema::table('sales', function (Blueprint $table) {
            $table->string('sales_channel', 25)->default('register')->after('pricing_mode'); // register, online
            $table->foreignId('sales_agent_id')->nullable()->after('sales_channel')->constrained('users')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->after('sales_agent_id')->constrained('subscriptions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['sales_agent_id']);
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['sales_channel', 'sales_agent_id', 'subscription_id']);
        });

        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_packages');
    }
};

