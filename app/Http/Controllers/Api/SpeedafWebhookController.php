<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Shipping\Speedaf\SpeedafCipherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpeedafWebhookController extends Controller
{
    /**
     * Handle incoming webhook updates from Speedaf Express
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            Log::info('Speedaf webhook received', ['payload' => $content]);

            $payload = null;

            // 1. Try parsing plain JSON
            if ($request->isJson() || (str_starts_with(trim($content), '{') || str_starts_with(trim($content), '['))) {
                $payload = json_decode($content, true);
            }

            // 2. If body is encrypted or wrapped in data
            if (isset($payload['data']) && is_string($payload['data'])) {
                // If encrypted, try decrypting using the matching gateway's secret key
                $billCode = $payload['billCode'] ?? null;
                if ($billCode) {
                    $shipment = Shipment::withoutGlobalScopes()
                        ->where('tracking_number', $billCode)
                        ->where('provider', 'speedaf')
                        ->first();

                    if ($shipment) {
                        $gateway = \App\Models\ShippingGateway::withoutGlobalScopes()
                            ->where('tenant_id', $shipment->tenant_id)
                            ->where('provider', 'speedaf')
                            ->first();

                        $secretKey = $gateway?->credentials['secret_key'] ?? null;
                        if ($secretKey) {
                            try {
                                $payload = SpeedafCipherService::decryptResponse($payload['data'], $secretKey);
                            } catch (\Throwable $e) {
                                Log::warning('Speedaf webhook decryption failed: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }

            if (!empty($payload)) {
                $items = isset($payload[0]) ? $payload : [$payload];

                foreach ($items as $item) {
                    $billCode = $item['billCode'] ?? ($item['waybillNo'] ?? ($item['mailNo'] ?? null));
                    $action   = (string) ($item['action'] ?? ($item['actionCode'] ?? ''));

                    if (!$billCode) {
                        continue;
                    }

                    $shipment = Shipment::withoutGlobalScopes()
                        ->where('tracking_number', $billCode)
                        ->where('provider', 'speedaf')
                        ->first();

                    if ($shipment) {
                        $newStatus = match ($action) {
                            '10'          => 'created',
                            '1', '150'    => 'picked',
                            '2', '3'      => 'in_transit',
                            '4'           => 'out_for_delivery',
                            '5', '16'     => 'delivered',
                            '-10'         => 'cancelled',
                            '-710', '730' => 'returned',
                            default       => $shipment->status,
                        };

                        $shipment->update(['status' => $newStatus]);

                        if ($shipment->order) {
                            if (in_array($action, ['5', '16'])) {
                                $shipment->order->update(['status' => 'delivered']);
                            } elseif (in_array($action, ['-710', '730'])) {
                                $shipment->order->update(['status' => 'returned']);
                            } elseif ($action === '-10') {
                                $shipment->order->update(['status' => 'cancelled']);
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'code'    => '0',
                'message' => 'success',
            ]);
        } catch (\Throwable $e) {
            Log::error('Speedaf webhook exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'code'    => '500',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
