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
        Schema::create('conversion_api_pixels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30); // facebook, tiktok, snapchat
            $table->string('pixel_id', 100);
            $table->text('access_token');
            $table->string('test_event_code', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('events')->nullable(); // e.g. ['Purchase']
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['platform', 'pixel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_api_pixels');
    }
};
