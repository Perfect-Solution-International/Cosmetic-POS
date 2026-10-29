<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sale_payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'bank_transfer', 'cheque', 'loyalty_points', 'credit') DEFAULT 'cash'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sale_payments MODIFY COLUMN payment_method ENUM('cash', 'card', 'bank_transfer', 'loyalty_points') DEFAULT 'cash'");
        }
    }
};

