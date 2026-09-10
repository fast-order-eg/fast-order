<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * إزالة أي سلات متروكة قديمة لا تحتوي على رقم هاتف صالح للتواصل
     */
    public function up(): void
    {
        try {
            DB::table('abandoned_carts')
                ->whereNull('converted_order_id')
                ->where(function ($q) {
                    $q->whereNull('phone')
                      ->orWhere('phone', '')
                      ->orWhereRaw('LENGTH(TRIM(phone)) < 8');
                })
                ->delete();
        } catch (\Throwable $e) {
            // Ignore if table error occurs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
