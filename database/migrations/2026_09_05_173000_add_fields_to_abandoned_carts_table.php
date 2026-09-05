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
        Schema::table('abandoned_carts', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('phone');
            $table->string('governorate')->nullable()->after('customer_name');
            $table->text('customer_address')->nullable()->after('governorate');
            $table->decimal('subtotal', 10, 2)->default(0)->after('cart_data');
            $table->decimal('total', 10, 2)->default(0)->after('subtotal');
            $table->string('status', 30)->default('abandoned')->after('total');
            $table->unsignedBigInteger('converted_order_id')->nullable()->after('status');
            $table->timestamp('last_contacted_at')->nullable()->after('converted_order_id');
            $table->text('notes')->nullable()->after('last_contacted_at');

            $table->foreign('converted_order_id')
                  ->references('id')
                  ->on('orders')
                  ->nullOnDelete();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abandoned_carts', function (Blueprint $table) {
            $table->dropForeign(['converted_order_id']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['tenant_id', 'phone']);
            $table->dropColumn([
                'customer_name',
                'governorate',
                'customer_address',
                'subtotal',
                'total',
                'status',
                'converted_order_id',
                'last_contacted_at',
                'notes',
            ]);
        });
    }
};
