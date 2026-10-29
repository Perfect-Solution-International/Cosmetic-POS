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
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->date('paid_date')->nullable()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_received_notes', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'paid_date']);
        });
    }
};

