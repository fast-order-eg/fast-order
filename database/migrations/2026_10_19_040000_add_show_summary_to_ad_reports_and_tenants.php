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
        Schema::table('ad_campaign_reports', function (Blueprint $table) {
            $table->boolean('show_summary')->default(false)->after('notes');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('meta_show_summary')->default(false)->after('meta_campaign_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_campaign_reports', function (Blueprint $table) {
            $table->dropColumn('show_summary');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('meta_show_summary');
        });
    }
};
