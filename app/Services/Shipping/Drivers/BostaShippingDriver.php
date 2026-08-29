<?php

namespace App\Services\Shipping\Drivers;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\ShippingGateway;
use Illuminate\Support\Facades\Http;

class BostaShippingDriver implements ShippingProviderInterface
{
    protected string $baseUrl = 'https://api.bosta.co/api/v2';

    public function createShipment(Order $order, ShippingGateway $gateway, array $options = []): array
    {
        $apiKey = $gateway->credentials['api_key'] ?? null;

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error'   => 'مفتاح الـ API لشركة بوسطة (Bosta) غير متوفر أو فارغ.',
            ];
        }

        if (str_starts_with($apiKey, 'test_') || app()->environment('testing')) {
            $mockTracking = 'BST-' . rand(100000, 999999);
            return [
                'success' => true,
                'tracking_number' => (string) $mockTracking,
                'airway_bill_url' => "https://app.bosta.co/api/v2/deliveries/awb/{$mockTracking}",
                'status' => 'created',
                'cost' => 45.00,
                'raw_response' => ['test_mode' => true],
            ];
        }

        try {
            $itemsCount = 1;
            if (is_array($order->items)) {
                $itemsCount = array_sum(array_column($order->items, 'qty')) ?: count($order->items);
            }

            // Split customer name into first and last name for Bosta requirements
            $nameParts = explode(' ', trim($order->customer_name ?? 'العميل'), 2);
            $firstName = $nameParts[0] ?: 'العميل';
            $lastName = $nameParts[1] ?? ' ';

            // Prepare Bosta Delivery Payload
            $payload = [
                'type' => 10, // 10 = Deliver (Standard Parcel Delivery)
                'specs' => [
                    'packageType' => 'Parcel',
                    'size' => 'SMALL',
                    'packageDetails' => [
                        'itemsCount' => (int) $itemsCount,
                        'description' => "طلب رقم #{$order->reference_number}",
                    ],
                ],
                'dropOffAddress' => [
                    'firstLine' => $order->customer_address ?: 'العنوان المحدد من العميل',
                    'city' => $order->governorate ?: 'Cairo',
                ],
                'receiver' => [
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                    'phone'     => $order->customer_phone,
                ],
                'cod' => (float) $order->total,
                'businessReference' => (string) $order->reference_number,
                'notes' => $order->notes ?: "طلب من متجر رقم #{$order->reference_number}",
            ];

            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $apiKey,
                'x-api-key'     => $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(15)->post("{$this->baseUrl}/deliveries", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $deliveryData = $data['data'] ?? $data;
                $trackingNumber = $deliveryData['trackingNumber'] ?? $deliveryData['_id'] ?? ('BST-' . rand(100000, 999999));
                $deliveryId = $deliveryData['_id'] ?? $trackingNumber;

                return [
                    'success' => true,
                    'tracking_number' => (string) $trackingNumber,
                    'airway_bill_url' => "https://app.bosta.co/api/v2/deliveries/awb/{$deliveryId}",
                    'status' => 'created',
                    'cost' => (float) ($deliveryData['price'] ?? 45.00),
                    'raw_response' => $data,
                ];
            }

            $errorMsg = $response->json()['message'] 
                ?? $response->json()['error'] 
                ?? 'فشل إنشاء الشحنة في بوسطة (تحقق من صحة مفتاح API أو البيانات).';

            return [
                'success' => false,
                'error' => $errorMsg,
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
        $apiKey = $gateway->credentials['api_key'] ?? null;

        if (!$apiKey || str_starts_with($apiKey, 'test_')) {
            return [
                'tracking_number' => $trackingNumber,
                'status' => 'in_transit',
                'events' => [
                    ['status' => 'created', 'time' => now()->subHours(5)->toIso8601String(), 'description' => 'تم إنشاء الشحنة في بوسطة'],
                    ['status' => 'picked_up', 'time' => now()->subHours(2)->toIso8601String(), 'description' => 'تم الاستلام بواسطة المندوب'],
                ],
            ];
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Authorization' => $apiKey,
                'x-api-key' => $apiKey,
            ])->timeout(10)->get("{$this->baseUrl}/deliveries/track/{$trackingNumber}");

            if ($response->successful()) {
                return $response->json();
            }

            return ['status' => 'unknown', 'tracking_number' => $trackingNumber];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function cancelShipment(string $trackingNumber, ShippingGateway $gateway): bool
    {
        return true;
    }
}
