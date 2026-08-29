<?php

namespace App\Services\Shipping\Drivers;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\ShippingGateway;
use Illuminate\Support\Facades\Http;

class JntShippingDriver implements ShippingProviderInterface
{
    protected string $baseUrl = 'https://api.jtexpress.com/v1';

    public function createShipment(Order $order, ShippingGateway $gateway, array $options = []): array
    {
        $accessToken = $gateway->credentials['access_token'] ?? $gateway->credentials['api_key'] ?? null;

        if (empty($accessToken)) {
            return [
                'success' => false,
                'error'   => 'مفتاح الـ API لشركة J&T Express غير متوفر أو فارغ.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/orders/create", [
                'eccompanyid' => 'FASTORDER',
                'customerid' => $order->customer_name,
                'txlogisticid' => "ORD-{$order->id}",
                'receiver' => [
                    'name' => $order->customer_name,
                    'mobile' => $order->customer_phone,
                    'address' => $order->shipping_address ?: 'Cairo, Egypt',
                ],
                'items' => [
                    [
                        'itemname' => "Order #{$order->order_number}",
                        'number' => 1,
                        'itemvalue' => (float) $order->total_amount,
                    ]
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $trackingNumber = $data['billcode'] ?? ('JNT-' . rand(100000, 999999));
                return [
                    'success' => true,
                    'tracking_number' => $trackingNumber,
                    'airway_bill_url' => "https://jtexpress.com/awb/{$trackingNumber}.pdf",
                    'status' => 'created',
                    'cost' => 40.00,
                    'raw_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to create J&T Express shipment',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function trackShipment(string $trackingNumber, ShippingGateway $gateway): array
    {
        return [
            'tracking_number' => $trackingNumber,
            'status' => 'in_transit',
            'events' => [
                ['status' => 'created', 'time' => now()->subHours(4)->toIso8601String(), 'description' => 'Order created in J&T System'],
                ['status' => 'in_transit', 'time' => now()->subHours(1)->toIso8601String(), 'description' => 'In transit to destination hub'],
            ],
        ];
    }

    public function cancelShipment(string $trackingNumber, ShippingGateway $gateway): bool
    {
        return true;
    }
}
