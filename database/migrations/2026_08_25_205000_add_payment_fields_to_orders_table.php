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
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `orders` MODIFY `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod'");
        } catch (\Throwable $e) {
            // Ignore if already modified or SQLite
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status', 30)->default('pending_cash')->after('payment_method')->index();
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id', 150)->nullable()->after('payment_status')->index();
            }
            if (!Schema::hasColumn('orders', 'payment_details')) {
                $table->json('payment_details')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('orders', 'fee_amount')) {
                $table->decimal('fee_amount', 10, 2)->default(0.00)->after('shipping_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'transaction_id', 'payment_details', 'fee_amount']);
        });
    }
};
