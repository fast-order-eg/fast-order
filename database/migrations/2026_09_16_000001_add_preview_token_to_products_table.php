<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'preview_token')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('preview_token', 64)->nullable()->index()->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'preview_token')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('preview_token');
            });
        }
    }
};
