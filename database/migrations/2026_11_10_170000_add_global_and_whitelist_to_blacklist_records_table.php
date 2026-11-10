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
        Schema::table('blacklist_records', function (Blueprint $table) {
            // Allow tenant_id to be nullable for platform-wide records
            $table->unsignedBigInteger('tenant_id')->nullable()->change();

            if (!Schema::hasColumn('blacklist_records', 'is_global')) {
                $table->boolean('is_global')->default(false)->after('reason');
                $table->index('is_global');
            }

            if (!Schema::hasColumn('blacklist_records', 'is_whitelisted')) {
                $table->boolean('is_whitelisted')->default(false)->after('is_global');
                $table->index('is_whitelisted');
            }

            $table->index(['type', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blacklist_records', function (Blueprint $table) {
            if (Schema::hasColumn('blacklist_records', 'is_whitelisted')) {
                $table->dropColumn('is_whitelisted');
            }
            if (Schema::hasColumn('blacklist_records', 'is_global')) {
                $table->dropColumn('is_global');
            }
        });
    }
};
