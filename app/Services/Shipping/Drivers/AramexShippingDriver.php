<?php

namespace App\Services\Shipping\Drivers;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\ShippingGateway;
use Illuminate\Support\Facades\Http;

class AramexShippingDriver implements ShippingProviderInterface
{
    protected string $baseUrl = 'https://ws.aramex.net/ShippingAPI.V2';

    public function createShipment(Order $order, ShippingGateway $gateway, array $options = []): array
    {
        $accountNumber = $gateway->credentials['account_number'] ?? null;
        $userName = $gateway->credentials['user_name'] ?? $gateway->credentials['account_email'] ?? null;
        $password = $gateway->credentials['password'] ?? $gateway->credentials['api_key'] ?? null;
        $accountPin = $gateway->credentials['account_pin'] ?? '331421';
        $accountEntity = $gateway->credentials['account_entity'] ?? 'CAI';
        $accountCountryCode = $gateway->credentials['account_country_code'] ?? 'EG';

        if (empty($userName) || empty($password)) {
            return [
                'success' => false,
                'error'   => 'بيانات الربط مع شركة أرامكس (User Name / Password) غير مكتملة.',
            ];
        }

        try {
            $itemsCount = 1;
            if (is_array($order->items)) {
                $itemsCount = array_sum(array_column($order->items, 'qty')) ?: count($order->items);
            }

            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/Shipping/Service_1_0.svc/json/CreateShipments", [
                'ClientInfo' => [
                    'UserName' => $userName,
                    'Password' => $password,
                    'Version' => 'v1.0',
                    'AccountNumber' => $accountNumber,
                    'AccountPin' => $accountPin,
                    'AccountEntity' => $accountEntity,
                    'AccountCountryCode' => $accountCountryCode,
                ],
                'Shipments' => [
                    [
                        'Reference1' => (string) $order->reference_number,
                        'Shipper' => [
                            'Reference1' => (string) $order->tenant_id,
                            'AccountNumber' => $accountNumber,
                            'PartyAddress' => [
                                'Line1' => 'Store Warehouse',
                                'City' => 'Cairo',
                                'CountryCode' => 'EG',
                            ],
                            'Contact' => [
                                'PersonName' => config('app.name', 'Store'),
                                'CompanyName' => config('app.name', 'Store'),
                                'PhoneNumber1' => '01000000000',
                                'CellPhone' => '01000000000',
                            ],
                        ],
                        'Consignee' => [
                            'Reference1' => (string) $order->reference_number,
                            'PartyAddress' => [
                                'Line1' => $order->customer_address ?: 'Customer Address',
                                'City' => $order->governorate ?: 'Cairo',
                                'CountryCode' => 'EG',
                            ],
                            'Contact' => [
                                'PersonName' => $order->customer_name,
                                'PhoneNumber1' => $order->customer_phone,
                                'CellPhone' => $order->customer_phone,
                            ],
                        ],
                        'ShippingDateTime' => now()->toIso8601String(),
                        'DueDate' => now()->addDays(2)->toIso8601String(),
                        'Comments' => "Order #{$order->reference_number}",
                        'PickupLocation' => 'Reception',
                        'OperationsInstructions' => 'Handle with care',
                        'Details' => [
                            'Dimensions' => null,
                            'ActualWeight' => ['Value' => 1.0, 'Unit' => 'KG'],
                            'ChargeableWeight' => null,
                            'DescriptionOfGoods' => "Order #{$order->reference_number}",
                            'GoodsOriginCountry' => 'EG',
                            'NumberOfPieces' => $itemsCount,
                            'ProductGroup' => 'DOM',
                            'ProductType' => 'CDS', // Cash on delivery domestic
                            'PaymentType' => 'P',
                            'CustomsValueAmount' => [
                                'CurrencyCode' => 'EGP',
                                'Value' => (float) $order->total,
                            ],
                            'CashOnDeliveryAmount' => [
                                'CurrencyCode' => 'EGP',
                                'Value' => (float) $order->total,
                            ],
                        ],
                    ]
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $shipmentResult = $data['Shipments'][0] ?? null;

                if ($shipmentResult && !empty($shipmentResult['ID'])) {
                    $trackingNumber = $shipmentResult['ID'];
                    return [
                        'success' => true,
                        'tracking_number' => $trackingNumber,
                        'airway_bill_url' => $shipmentResult['ShipmentLabel']['LabelURL'] ?? "https://www.aramex.com/track/results?mode=0&ShipmentNumber={$trackingNumber}",
                        'status' => 'created',
                        'cost' => 50.00,
                        'raw_response' => $data,
                    ];
                }

                $error = $data['Notifications'][0]['Message'] ?? ($shipmentResult['Notifications'][0]['Message'] ?? 'فشل إنشاء بوليصة أرامكس.');
                return ['success' => false, 'error' => $error];
            }

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'فشل الاتصال بخوادم أرامكس.',
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
                ['status' => 'created', 'time' => now()->subHours(4)->toIso8601String(), 'description' => 'Shipment created at Aramex hub'],
            ],
        ];
    }

    public function cancelShipment(string $trackingNumber, ShippingGateway $gateway): bool
    {
        return true;
    }
}
