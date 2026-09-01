<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'فحص الاشتراكات المنتهية وتعليق المتاجر المنتهية تلقائياً';

    public function handle(): void
    {
        $this->info('جاري فحص الاشتراكات والخطط المجانية المنتهية...');
        
        $tenants = \App\Models\Tenant::where(function ($q) {
            $q->where(function ($sq) {
                $sq->whereNotNull('subscription_ends_at')->where('subscription_ends_at', '<', now());
            })->orWhere(function ($sq) {
                $sq->whereNotNull('trial_ends_at')->where('trial_ends_at', '<', now())->whereNull('subscription_ends_at');
            });
        })->get();

        $count = 0;
        foreach ($tenants as $tenant) {
            if (!$tenant->isCommissionPlan()) {
                if ($tenant->subscription_status !== 'expired') {
                    $tenant->update([
                        'subscription_status' => 'expired',
                    ]);
                    $count++;
                }
            }
        }

        \Illuminate\Support\Facades\Log::info("[Scheduler] Checked expired subscriptions: marked {$count} tenants as expired.");
        $this->info("تم فحص المتاجر وتحديث {$count} متجر منتهي بنجاح.");
    }
}
