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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'whatsapp_status')) {
                $table->string('whatsapp_status', 30)->default('none')->after('notes');
            }
            if (!Schema::hasColumn('orders', 'whatsapp_message_id')) {
                $table->string('whatsapp_message_id', 100)->nullable()->after('whatsapp_status');
            }
            if (!Schema::hasColumn('orders', 'whatsapp_sent_at')) {
                $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_message_id');
            }
            if (!Schema::hasColumn('orders', 'whatsapp_response_at')) {
                $table->timestamp('whatsapp_response_at')->nullable()->after('whatsapp_sent_at');
            }
            if (!Schema::hasColumn('orders', 'whatsapp_charge_amount')) {
                $table->decimal('whatsapp_charge_amount', 8, 2)->default(0.00)->after('whatsapp_response_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_status',
                'whatsapp_message_id',
                'whatsapp_sent_at',
                'whatsapp_response_at',
                'whatsapp_charge_amount',
            ]);
        });
    }
};
