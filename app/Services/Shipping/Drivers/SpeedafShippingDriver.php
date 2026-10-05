<?php

namespace App\Services\Shipping\Drivers;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingGateway;
use App\Services\Shipping\Speedaf\SpeedafCipherService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpeedafShippingDriver implements ShippingProviderInterface
{
    /**
     * Speedaf API Base URLs
     */
    protected string $liveBaseUrl = 'https://apis.speedaf.com';
    protected string $sandboxBaseUrl = 'https://uat-api.speedaf.com';

    /**
     * Create a new shipment order in Speedaf Express
     */
    public function createShipment(Order $order, ShippingGateway $gateway, array $options = []): array
    {
        $creds = $gateway->credentials ?? [];
        $appCode      = $creds['app_code'] ?? null;
        $secretKey    = $creds['secret_key'] ?? null;
        $customerCode = $creds['customer_code'] ?? null;
        $platformSource = $creds['platform_source'] ?? 'csp';
        $isSandbox    = (bool) ($creds['is_sandbox'] ?? false);
        $isAllowOpen  = (bool) ($creds['is_allow_open'] ?? false);

        if (empty($appCode) || empty($secretKey) || empty($customerCode)) {
            return [
                'success' => false,
                'error'   => 'بيانات الربط لشركة Speedaf غير مكتملة (يرجى إدخال App Code ومفتاح Secret Key وكود العميل Customer Code).',
            ];
        }

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        try {
            // 1. Payment Type & COD Amount
            $isCOD = in_array(strtolower($order->payment_method ?? 'cod'), ['cod', 'cash', 'cash_on_delivery', '']);
            $codFee = $isCOD ? round((float) $order->total, 2) : 0.0;

            // 2. Prepare Items
            $rawItems = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
            $itemsList = [];
            $totalQty = 0;

            if (!empty($rawItems)) {
                foreach ($rawItems as $idx => $item) {
                    $qty = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));
                    $price = round((float) ($item['price'] ?? 0), 2);
                    $totalQty += $qty;

                    $itemName = mb_substr($item['name'] ?? 'منتج', 0, 95);
                    $itemsList[] = [
                        'sku'              => (string) ($item['id'] ?? ($idx + 1)),
                        'goodsName'        => $itemName,
                        'goodsNameDialect' => $itemName,
                        'goodsQTY'         => $qty,
                        'goodsValue'       => max(1, $price),
                        'goodsType'        => 'IT02', // General goods
                        'blInsure'         => 0,
                        'battery'          => 0,
                    ];
                }
            } else {
                $totalQty = 1;
                $itemsList[] = [
                    'sku'              => 'ORD-' . $order->reference_number,
                    'goodsName'        => "طلب رقم #{$order->reference_number}",
                    'goodsNameDialect' => "طلب رقم #{$order->reference_number}",
                    'goodsQTY'         => 1,
                    'goodsValue'       => max(1, round((float) $order->total, 2)),
                    'goodsType'        => 'IT02',
                    'blInsure'         => 0,
                    'battery'          => 0,
                ];
            }

            // 3. Sender Info (Store / Merchant)
            $tenantId = $order->tenant_id;
            $tenant = $order->tenant;
            $storeName = Setting::get('store_name', null, $tenantId) ?: ($tenant?->name ?? 'متجر إلكتروني');
            $rawStorePhone = Setting::get('phone', null, $tenantId) ?: ($tenant?->owner?->phone ?? '01000000000');
            $storePhone = $this->formatPhone($rawStorePhone);

            $senderProvince = Setting::get('sender_governorate', null, $tenantId) ?: 'القاهرة';
            $senderCity     = Setting::get('sender_city', null, $tenantId) ?: $senderProvince;
            $senderAddress  = Setting::get('address', null, $tenantId) ?: (Setting::get('pickup_address', null, $tenantId) ?: $senderProvince);

            // 4. Receiver Info
            $receiverName   = $order->customer_name ?: 'عميل';
            $receiverPhone  = $this->formatPhone($order->customer_phone);
            $receiverProv   = $order->governorate ?: 'القاهرة';
            $receiverCity   = $order->city ?: $receiverProv;
            $receiverAddr   = $order->customer_address ?: ($order->shipping_address ?: 'العنوان بالتفصيل');

            // 5. Build Create Order Payload
            $customOrderNo = 'ORD_' . $order->id . '_' . $order->reference_number;
            $orderPayload = [
                'customerCode'        => $customerCode,
                'customOrderNo'       => $customOrderNo,
                'platformSource'      => $platformSource,
                'parcelType'          => 'PT01', // Standard parcel
                'deliveryType'        => 'DE01', // Door-to-door delivery
                'transportType'       => 'TT02', // Land/Air Express
                'shipType'            => 'ST01', // Standard express
                'payMethod'           => 'PA01', // Standard collection
                'isAllowOpen'         => $isAllowOpen ? 1 : 0,

                // Receiver details
                'acceptName'          => mb_substr($receiverName, 0, 100),
                'acceptMobile'        => $receiverPhone,
                'acceptAddress'       => mb_substr($receiverAddr, 0, 500),
                'acceptCountryCode'   => 'EG',
                'acceptCountryName'   => 'Egypt',
                'acceptProvinceName'  => $receiverProv,
                'acceptCityName'      => $receiverCity,
                'acceptDistrictName'  => $receiverCity,

                // Sender details
                'sendName'            => mb_substr($storeName, 0, 100),
                'sendMobile'          => $storePhone,
                'sendAddress'         => mb_substr($senderAddress, 0, 500),
                'sendCountryCode'     => 'EG',
                'sendCountryName'     => 'Egypt',
                'sendProvinceName'    => $senderProvince,
                'sendCityName'        => $senderCity,
                'sendDistrictName'    => $senderCity,

                // Parcel details
                'piece'               => 1,
                'goodsQTY'            => $totalQty,
                'parcelWeight'        => max(0.5, (float) ($options['weight'] ?? 1.0)),
                'parcelValue'         => round((float) $order->total, 2),
                'parcelCurrencyType'  => 'EGP',
                'codFee'              => $codFee,
                'currencyType'        => 'EGP',
                'remark'              => $this->buildRemark($order),
                'itemList'            => $itemsList,
            ];

            // 6. Encrypt Payload & Send Request
            $encrypted = SpeedafCipherService::encryptPayload($orderPayload, $secretKey);
            $url = $baseUrl . '/open-api/express/order/createOrder?appCode=' . urlencode($appCode) . '&timestamp=' . $encrypted['timestamp'];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'text/plain',
                    'Accept'       => 'application/json',
                ])
                ->withBody($encrypted['body'], 'text/plain')
                ->post($url);

            if ($response->failed()) {
                Log::error('Speedaf createOrder HTTP error', [
                    'order_id' => $order->id,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error'   => 'تعذر الاتصال بخوادم Speedaf (كود الخطأ: ' . $response->status() . ').',
                ];
            }

            $respJson = $response->json();

            // Handle outer API error
            if (isset($respJson['success']) && !$respJson['success']) {
                $errCode = $respJson['error']['code'] ?? 'Unknown';
                $errMsg  = $respJson['error']['message'] ?? 'فشل طلب إنشاء الشحنة في Speedaf';
                return [
                    'success' => false,
                    'error'   => "خطأ من شركة Speedaf [{$errCode}]: {$errMsg}",
                ];
            }

            // Decrypt Response Data
            $decryptedData = null;
            if (!empty($respJson['data'])) {
                if (is_string($respJson['data'])) {
                    $decryptedData = SpeedafCipherService::decryptResponse($respJson['data'], $secretKey);
                } else {
                    $decryptedData = $respJson['data'];
                }
            }

            // Verify inner response success
            if (isset($decryptedData['success']) && !$decryptedData['success']) {
                $msg = $decryptedData['message'] ?? 'فشلت معالجة الشحنة في نظام Speedaf';
                return [
                    'success' => false,
                    'error'   => "خطأ Speedaf: {$msg}",
                ];
            }

            $billCode = $decryptedData['billCode'] ?? ($decryptedData['waybillNo'] ?? null);

            if (empty($billCode)) {
                return [
                    'success' => false,
                    'error'   => 'لم يتم إرجاع رقم بوليصة الشحن (billCode) من Speedaf.',
                    'raw_response' => $decryptedData,
                ];
            }

            // 7. Auto fetch Waybill Label PDF URL
            $airwayBillUrl = null;
            try {
                $airwayBillUrl = $this->printWaybill([$billCode], $gateway);
            } catch (\Throwable $e) {
                Log::warning('Speedaf auto fetch waybill PDF failed', [
                    'billCode' => $billCode,
                    'error'    => $e->getMessage(),
                ]);
            }

            return [
                'success'         => true,
                'tracking_number' => $billCode,
                'airway_bill_url' => $airwayBillUrl,
                'status'          => 'created',
                'cost'            => (float) ($order->shipping_cost ?? 0.0),
                'raw_response'    => $decryptedData,
            ];

        } catch (\Throwable $e) {
            Log::error('Speedaf createShipment exception: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace'    => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => 'حدث خطأ أثناء إنشاء الشحنة مع Speedaf: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Print Waybill / Get Airway Bill PDF URL
     */
    public function printWaybill(array $waybillNumbers, ShippingGateway $gateway): ?string
    {
        $creds = $gateway->credentials ?? [];
        $appCode   = $creds['app_code'] ?? null;
        $secretKey = $creds['secret_key'] ?? null;
        $isSandbox = (bool) ($creds['is_sandbox'] ?? false);

        if (empty($appCode) || empty($secretKey) || empty($waybillNumbers)) {
            return null;
        }

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        $payload = [
            'waybillNoList' => array_values($waybillNumbers),
            'labelType'     => 5, // 2sheets with logo (10x15 thermal standard)
            'withLogo'      => true,
        ];

        $encrypted = SpeedafCipherService::encryptPayload($payload, $secretKey);
        $url = $baseUrl . '/open-api/express/order/print?appCode=' . urlencode($appCode) . '&timestamp=' . $encrypted['timestamp'];

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'text/plain'])
            ->withBody($encrypted['body'], 'text/plain')
            ->post($url);

        if ($response->successful()) {
            $respJson = $response->json();
            if (!empty($respJson['data'])) {
                $data = is_string($respJson['data'])
                    ? SpeedafCipherService::decryptResponse($respJson['data'], $secretKey)
                    : $respJson['data'];

                if (!empty($data['urls'][0])) {
                    return $data['urls'][0];
                }
                if (!empty($data['orderLabels'][0]['labelUrl'])) {
                    return $data['orderLabels'][0]['labelUrl'];
                }
            }
        }

        return null;
    }

    /**
     * Track a shipment by Speedaf waybill number
     */
    public function trackShipment(string $trackingNumber, ShippingGateway $gateway): array
    {
        $creds = $gateway->credentials ?? [];
        $appCode   = $creds['app_code'] ?? null;
        $secretKey = $creds['secret_key'] ?? null;
        $isSandbox = (bool) ($creds['is_sandbox'] ?? false);

        if (empty($appCode) || empty($secretKey)) {
            return [
                'success' => false,
                'error'   => 'بيانات الربط لشركة Speedaf غير متوفرة.',
            ];
        }

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        $payload = [
            'mailNoList' => [$trackingNumber],
        ];

        $encrypted = SpeedafCipherService::encryptPayload($payload, $secretKey);
        $url = $baseUrl . '/open-api/express/track/query?appCode=' . urlencode($appCode) . '&timestamp=' . $encrypted['timestamp'];

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'text/plain'])
            ->withBody($encrypted['body'], 'text/plain')
            ->post($url);

        if ($response->failed()) {
            return [
                'success' => false,
                'error'   => 'فشل الاتصال بخدمة تتبع Speedaf.',
            ];
        }

        $respJson = $response->json();
        if (isset($respJson['success']) && !$respJson['success']) {
            return [
                'success' => false,
                'error'   => $respJson['error']['message'] ?? 'فشل استعلام التتبع',
            ];
        }

        $data = is_string($respJson['data'] ?? null)
            ? SpeedafCipherService::decryptResponse($respJson['data'], $secretKey)
            : ($respJson['data'] ?? []);

        $tracks = [];
        $latestStatus = 'unknown';

        if (is_array($data) && !empty($data[0]['tracks'])) {
            $tracks = $data[0]['tracks'];
            $latestTrack = end($tracks) ?: [];
            $actionCode = (string) ($latestTrack['action'] ?? '');

            $latestStatus = match ($actionCode) {
                '10'        => 'created',
                '1', '150'  => 'picked',
                '2', '3'    => 'in_transit',
                '4'         => 'out_for_delivery',
                '5', '16'   => 'delivered',
                '-10'       => 'cancelled',
                '-710', '730' => 'returned',
                default     => 'in_transit',
            };
        }

        return [
            'success'         => true,
            'tracking_number' => $trackingNumber,
            'status'          => $latestStatus,
            'tracks'          => $tracks,
            'raw_response'    => $data,
        ];
    }

    /**
     * Cancel a shipment in Speedaf
     */
    public function cancelShipment(string $trackingNumber, ShippingGateway $gateway, ?string $txlogisticId = null): bool
    {
        $creds = $gateway->credentials ?? [];
        $appCode      = $creds['app_code'] ?? null;
        $secretKey    = $creds['secret_key'] ?? null;
        $customerCode = $creds['customer_code'] ?? null;
        $isSandbox    = (bool) ($creds['is_sandbox'] ?? false);

        if (empty($appCode) || empty($secretKey) || empty($customerCode)) {
            return false;
        }

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        $payload = [
            [
                'customerCode' => $customerCode,
                'billCode'     => $trackingNumber,
                'cancelReason' => 'تم إلغاء الطلب من قِبل المتجر',
                'cancelBy'     => 'Merchant',
            ],
        ];

        $encrypted = SpeedafCipherService::encryptPayload($payload, $secretKey);
        $url = $baseUrl . '/open-api/express/order/cancelOrder?appCode=' . urlencode($appCode) . '&timestamp=' . $encrypted['timestamp'];

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'text/plain'])
            ->withBody($encrypted['body'], 'text/plain')
            ->post($url);

        if ($response->successful()) {
            $respJson = $response->json();
            if (isset($respJson['success']) && $respJson['success']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build clean readable item remarks for the courier
     */
    protected function buildRemark(Order $order): string
    {
        $rawItems = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
        $itemDescriptions = [];

        foreach ($rawItems as $item) {
            $name = $item['name'] ?? $item['product_name'] ?? 'منتج';
            $qty = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));

            $opts = [];
            if (!empty($item['selectedSize']) || !empty($item['size'])) {
                $opts[] = 'مقاس: ' . ($item['selectedSize'] ?? $item['size']);
            }
            if (!empty($item['selectedColor']) || !empty($item['color'])) {
                $opts[] = 'لون: ' . ($item['selectedColor'] ?? $item['color']);
            }

            $optStr = !empty($opts) ? ' (' . implode(', ', $opts) . ')' : '';
            $itemDescriptions[] = "{$name} x{$qty}{$optStr}";
        }

        $remark = implode(' | ', $itemDescriptions);

        if (!empty($order->notes)) {
            $cleanNotes = preg_replace('/\[.*واتساب.*\]/iu', '', $order->notes);
            $cleanNotes = trim($cleanNotes);
            if (!empty($cleanNotes)) {
                $remark .= ' | ملاحظة: ' . $cleanNotes;
            }
        }

        if (empty(trim($remark))) {
            $remark = "طلب رقم #{$order->reference_number}";
        }

        return mb_substr($remark, 0, 200);
    }

    /**
     * Format Egyptian phone number to standard format
     */
    protected function formatPhone(?string $phone): string
    {
        if (!$phone) {
            return '01000000000';
        }

        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '20') && strlen($clean) > 10) {
            $clean = substr($clean, 2);
        }

        if (!str_starts_with($clean, '0') && strlen($clean) === 10) {
            $clean = '0' . $clean;
        }

        return $clean ?: '01000000000';
    }
}
