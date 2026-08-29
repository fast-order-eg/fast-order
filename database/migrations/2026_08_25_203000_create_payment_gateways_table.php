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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('provider', 50); // cod, paymob, kashier, fawry, stripe, etc.
            $table->boolean('is_active')->default(false);
            $table->string('display_name')->nullable();
            $table->text('display_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('credentials')->nullable(); // Encrypted / JSON credentials
            $table->json('settings')->nullable(); // Fee adjustment, mode, etc.
            $table->timestamps();

            $table->unique(['tenant_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
