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
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->index(); // SHA256 of endpoint for fast unique lookups
            $table->text('public_key'); // p256dh key
            $table->text('auth_token'); // auth secret
            $table->string('content_encoding', 20)->default('aesgcm');
            $table->string('device_name', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'endpoint_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
