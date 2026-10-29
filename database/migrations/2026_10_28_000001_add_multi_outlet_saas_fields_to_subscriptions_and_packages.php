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
        // 1. Extend Subscription Packages with Branch Add-on Pricing
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('included_branches')->default(1)->after('price');
            $table->decimal('price_per_additional_branch', 12, 2)->default(1500.00)->after('included_branches');
            $table->integer('max_branches')->nullable()->after('price_per_additional_branch');
        });

        // 2. Extend Subscriptions with Business Tenant, Shop Owner, Branch Quota & Calculation Breakdown
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('subscription_no');
            $table->foreignId('shop_owner_id')->nullable()->after('business_name')->constrained('users')->nullOnDelete();
            $table->integer('allocated_branches')->default(1)->after('package_id');
            $table->decimal('base_price', 12, 2)->default(0.00)->after('price');
            $table->decimal('additional_branch_price', 12, 2)->default(0.00)->after('base_price');
            $table->decimal('total_price', 12, 2)->default(0.00)->after('additional_branch_price');
            $table->string('billing_cycle', 25)->default('monthly')->after('total_price'); // trial, monthly, quarterly, biannual, yearly
        });

        // 3. Extend Branches with optional Owner assignment
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('type')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['shop_owner_id']);
            $table->dropColumn([
                'business_name',
                'shop_owner_id',
                'allocated_branches',
                'base_price',
                'additional_branch_price',
                'total_price',
                'billing_cycle',
            ]);
        });

        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn([
                'included_branches',
                'price_per_additional_branch',
                'max_branches',
            ]);
        });
    }
};

