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
}
