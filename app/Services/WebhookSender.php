<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookSender
{
    /**
     * Send webhook requests to active webhooks of the tenant for a given event.
     */
    public static function trigger(string $event, array $payload, $tenantId = null): void
    {
        if ($tenantId === null) {
            $tenantId = session()->get('tenant_id') ?? config('tenant.id');
        }

        $query = Webhook::withoutGlobalScopes()->where('is_active', true);
        
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        $webhooks = $query->get();

        foreach ($webhooks as $webhook) {
            $events = $webhook->events ?: [];
            if (!in_array($event, $events)) {
                continue;
            }

            self::sendSingleWebhook($webhook, $event, $payload);
        }
    }

    /**
     * Send payload to a single specific webhook and log the result.
     */
    public static function sendSingleWebhook(Webhook $webhook, string $event, array $payload): array
    {
        $payload = self::formatPayload($event, $payload);
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $webhook->secret);

        $startTime = microtime(true);
        $responseStatus = null;
        $responseBody = null;

        try {
            $response = Http::withHeaders([
                'X-FastOrder-Signature' => $signature,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(6) // 6 seconds timeout
            ->post($webhook->url, $payload);

            $responseStatus = $response->status();
            $responseBody = $response->body();
        } catch (\Exception $e) {
            $responseStatus = 500;
            $responseBody = 'Webhook Connection Error: ' . $e->getMessage();
            Log::error("Webhook delivery failed for URL {$webhook->url}: " . $e->getMessage());
        } finally {
            $duration = (int) round((microtime(true) - $startTime) * 1000);

            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $payload,
                'response_status' => $responseStatus,
                'response_body' => $responseBody ? substr($responseBody, 0, 65535) : null,
                'duration_ms' => $duration,
            ]);
        }

        return [
            'status' => $responseStatus,
            'body' => $responseBody,
            'duration_ms' => $duration,
        ];
    }

    /**
     * Normalize and format payload to ensure CRM and ERP compatibility (e.g. FasterSoft).
     */
    public static function formatPayload(string $event, array $payload): array
    {
        if (isset($payload['items']) && is_array($payload['items'])) {
            foreach ($payload['items'] as &$item) {
                if (!is_array($item)) {
                    continue;
                }

                $productId = $item['id'] ?? $item['product_id'] ?? null;
                $product = null;
                if ($productId) {
                    $product = \App\Models\Product::withoutGlobalScopes()->find($productId);
                }

                // Ensure product_id is explicitly set
                if (!isset($item['product_id']) && $productId) {
                    $item['product_id'] = $productId;
                }

                // Clean product name to match FasterSoft / ERP catalog
                if ($product && !empty($product->name)) {
                    if (!empty($item['name']) && $item['name'] !== $product->name) {
                        $item['bundle_title'] = $item['name'];
                    }
                    $item['name'] = $product->name;
                } elseif (!empty($item['name'])) {
                    $cleanName = preg_replace('/\s*\(\s*\d+\s*(?:قطع|قطعة|قطعه|pieces?)\s*\)$/ui', '', (string)$item['name']);
                    if ($cleanName && $cleanName !== $item['name']) {
                        $item['bundle_title'] = $item['name'];
                        $item['name'] = trim($cleanName);
                    }
                }

                // Ensure product image is set
                if (empty($item['image']) && $product) {
                    $item['image'] = $product->main_image_path
                        ? asset('storage/' . $product->main_image_path)
                        : ($product->image_url ?? null);
                }
            }
            unset($item);
        }

        return $payload;
    }
}

