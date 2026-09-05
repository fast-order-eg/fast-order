<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class CleanOrderNotes extends Command
{
    protected $signature = 'orders:clean-notes';
    protected $description = 'تنظيف حقل الملاحظات في الطلبات من رسائل وتنبيهات الواتساب الفنية';

    public function handle()
    {
        $this->info('Starting cleanup of order notes...');

        $orders = Order::withoutGlobalScopes()
            ->whereNotNull('notes')
            ->where(function ($q) {
                $q->where('notes', 'like', '%واتساب%')
                  ->orWhere('notes', 'like', '%whatsapp%')
                  ->orWhere('notes', 'like', '%[واتساب]%')
                  ->orWhere('notes', 'like', '%[whatsapp]%');
            })
            ->get();

        $cleanedCount = 0;

        foreach ($orders as $order) {
            $lines = preg_split("/\r\n|\n|\r/", $order->notes);
            $cleanLines = [];

            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed)) continue;

                // Skip lines with whatsapp technical tags
                if (str_contains($trimmed, 'واتساب') || str_contains($trimmed, 'whatsapp') || str_contains($trimmed, 'WhatsApp')) {
                    continue;
                }

                $cleanLines[] = $trimmed;
            }

            $cleanNotes = !empty($cleanLines) ? implode("\n", $cleanLines) : null;

            if ($cleanNotes !== $order->notes) {
                $order->update(['notes' => $cleanNotes]);
                $cleanedCount++;
            }
        }

        $this->info("Cleaned {$cleanedCount} orders successfully.");
        return Command::SUCCESS;
    }
}