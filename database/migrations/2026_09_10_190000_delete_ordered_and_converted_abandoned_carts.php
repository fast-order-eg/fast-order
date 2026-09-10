<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Tenant;
use App\Console\Commands\SyncAbandonedCartsWithOrders;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $tenantIds = Tenant::pluck('id');
            foreach ($tenantIds as $tId) {
                SyncAbandonedCartsWithOrders::syncForTenant($tId);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Migration delete_ordered_and_converted_abandoned_carts failed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation
    }
};
