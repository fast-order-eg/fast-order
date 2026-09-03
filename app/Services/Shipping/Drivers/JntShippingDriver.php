<?php

namespace App\Services\Shipping\Drivers;

use App\Contracts\ShippingProviderInterface;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ShippingGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JntShippingDriver implements ShippingProviderInterface
{
    /**
     * Base URLs for J&T Express Egypt Open Platform
     */
    protected string $liveBaseUrl = 'https://openapi.jtjms-eg.com/webopenplatformapi/api';
    protected string $sandboxBaseUrl = 'https://demoopenapi.jtjms-eg.com/webopenplatformapi/api';

    /**
     * Create shipment and pickup order in J&T Express
     */
    public function createShipment(Order $order, ShippingGateway $gateway, array $options = []): array
    {
        $creds = $gateway->credentials ?? [];
        $customerCode = $creds['customer_code'] ?? null;
        $apiAccount   = $creds['api_account'] ?? $creds['api_password'] ?? $creds['api_key'] ?? null;
        $privateKey   = $creds['private_key'] ?? null;
        $password     = $creds['password'] ?? $creds['vip_password'] ?? null;
        $isSandbox    = (bool) ($creds['is_sandbox'] ?? false);

        if (empty($customerCode) || empty($apiAccount) || empty($privateKey) || empty($password)) {
            return [
                'success' => false,
                'error'   => 'بيانات الربط لشركة J&T Express غير مكتملة (يرجى التأكد من كود العميل، اسم حساب API، المفتاح السري، وكلمة سر VIP).',
            ];
        }

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        try {
            // 1. Payment Type & Amounts
            $isCOD = in_array(strtolower($order->payment_method ?? 'cod'), ['cod', 'cash', 'cash_on_delivery', '']);
            $payType = $isCOD ? 'PP_PM' : 'FREIGHT_PREPAID';
            $codAmount = $isCOD ? (float) $order->total : 0.0;

            // 2. Format Items
            $rawItems = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
            $items = [];
            $totalQty = 0;

            if (!empty($rawItems)) {
                foreach ($rawItems as $item) {
                    $qty = max(1, (int) ($item['quantity'] ?? $item['qty'] ?? 1));
                    $price = (float) ($item['price'] ?? 0);
                    $totalQty += $qty;
                    $items[] = [
                        'itemName'  => mb_substr($item['name'] ?? 'منتج', 0, 100),
                        'number'    => $qty,
                        'itemValue' => $price,
                    ];
                }
            } else {
                $totalQty = 1;
                $items[] = [
                    'itemName'  => "طلب رقم #{$order->reference_number}",
                    'number'    => 1,
                    'itemValue' => (float) $order->total,
                ];
            }

            // 3. Sender Info (Store / Merchant)
            $tenant = $order->tenant;
            $storeName = Setting::get('store_name') ?: ($tenant?->name ?? 'متجر إلكتروني');
            $storePhone = $this->formatPhone(Setting::get('phone') ?: ($tenant?->owner?->phone ?? '01000000000'));
            $storeProv  = Setting::get('sender_governorate') ?: 'القاهرة';
            $storeCity  = Setting::get('sender_city') ?: 'القاهرة';
            $storeAddr  = Setting::get('pickup_address') ?: ($storeProv . ' - ' . $storeCity);

            // 4. Receiver Info (Customer)
            $receiverPhone = $this->formatPhone($order->customer_phone);
            $receiverProv  = $order->governorate ?: 'القاهرة';
            $receiverCity  = $order->city ?: $receiverProv;
            $receiverAddr  = $order->customer_address ?: ($order->shipping_address ?: 'العنوان بالتفصيل');

            // 5. Unique Transaction Logistic ID
            $txlogisticId = 'ORD_' . $order->id . '_' . $order->reference_number;

            // 6. Calculate Body Digest
            $bodyDigest = $this->calculateBodyDigest($customerCode, $password, $privateKey);

            // 7. Build Business Content Payload
            $bizContentArray = [
                'customerCode'         => (string) $customerCode,
                'digest'               => $bodyDigest,
                'serviceType'          => '01',
                'orderType'            => '1',
                'deliveryType'         => '04',
                'operateType'          => '1',
                'expressType'          => 'EZ',
                'payType'              => $payType,
                'priceCurrency'        => 'EGP',
                'txlogisticId'         => $txlogisticId,
                'goodsType'            => 'ITN1',
                'totalQuantity'        => $totalQty,
                'itemsValue'           => (float) $order->total,
                'collectionOnDelivery' => $codAmount,
                'sender' => [
                    'name'    => $storeName,
                    'mobile'  => $storePhone,
                    'prov'    => $storeProv,
                    'city'    => $storeCity,
                    'address' => $storeAddr,
                ],
                'receiver' => [
                    'name'    => $order->customer_name ?: 'عميل',
                    'mobile'  => $receiverPhone,
                    'prov'    => $receiverProv,
                    'city'    => $receiverCity,
                    'address' => $receiverAddr,
                ],
                'items'  => $items,
                'remark' => $order->notes ?: "طلب رقم #{$order->reference_number}",
            ];

            $bizContent = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE);
            $headerDigest = $this->calculateHeaderDigest($bizContent, $privateKey);
            $timestamp = (string) round(microtime(true) * 1000);

            Log::info("J&T Express Shipment Request [Order: {$order->id}]", [
                'txlogisticId' => $txlogisticId,
                'isSandbox'    => $isSandbox,
                'bizContent'   => $bizContentArray,
            ]);

            // 8. Send Request to J&T Open Platform
            $response = Http::asForm()
                ->withoutVerifying()
                ->withHeaders([
                    'apiAccount'   => $apiAccount,
                    'digest'       => $headerDigest,
                    'timestamp'    => $timestamp,
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                ])
                ->timeout(25)
                ->post("{$baseUrl}/order/addOrder", [
                    'bizContent' => $bizContent,
                ]);

            $resJson = $response->json() ?? [];

            Log::info("J&T Express Shipment Response [Order: {$order->id}]", [
                'status_code' => $response->status(),
                'response'    => $resJson,
            ]);

            // Check J&T Response
            $code = (string) ($resJson['code'] ?? '');
            $isSuccess = ($code === '1' || $code === '200' || strtoupper($code) === 'SUCCESS' || $code === '0');
            $data = $resJson['data'] ?? [];

            if ($response->successful() && ($isSuccess || !empty($data['billCode']) || !empty($data['billcode']))) {
                $trackingNumber = $data['billCode'] 
                    ?? $data['billcode'] 
                    ?? $data['mailNo'] 
                    ?? $data['waybillNo'] 
                    ?? $txlogisticId;

                $sortingCode = $data['sortingCode'] 
                    ?? $data['sortingcode'] 
                    ?? $data['sortCode'] 
                    ?? null;

                $airwayBillUrl = $data['airwayBillUrl'] 
                    ?? $data['billUrl'] 
                    ?? "https://www.jtexpress-eg.com/trajectoryQuery?bills={$trackingNumber}";

                return [
                    'success'         => true,
                    'tracking_number' => (string) $trackingNumber,
                    'sorting_code'    => $sortingCode,
                    'airway_bill_url' => $airwayBillUrl,
                    'status'          => 'created',
                    'cost'            => (float) ($data['shippingFee'] ?? 40.00),
                    'raw_response'    => $resJson,
                ];
            }

            $errMsg = $resJson['msg'] 
                ?? $resJson['message'] 
                ?? $resJson['errorMsg'] 
                ?? 'فشل إنشاء الشحنة مع J&T Express (يرجى مراجعة صحة البيانات والمفتاح السري).';

            return [
                'success' => false,
                'error'   => $errMsg,
                'raw_response' => $resJson,
            ];
        } catch (\Throwable $e) {
            Log::error("J&T Express Shipment Exception [Order: {$order->id}]: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => 'خطأ في الاتصال بسيرفر J&T Express: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Track shipment stages in J&T Express
     */
    public function trackShipment(string $trackingNumber, ShippingGateway $gateway): array
    {
        $creds = $gateway->credentials ?? [];
        $customerCode = $creds['customer_code'] ?? null;
        $apiAccount   = $creds['api_account'] ?? $creds['api_password'] ?? $creds['api_key'] ?? null;
        $privateKey   = $creds['private_key'] ?? null;
        $password     = $creds['password'] ?? $creds['vip_password'] ?? null;
        $isSandbox    = (bool) ($creds['is_sandbox'] ?? false);

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        if (empty($customerCode) || empty($apiAccount) || empty($privateKey)) {
            return [
                'tracking_number' => $trackingNumber,
                'status'          => 'unknown',
                'events'          => [],
            ];
        }

        try {
            $bizContentArray = [
                'customerCode' => (string) $customerCode,
                'billCodes'    => $trackingNumber,
            ];

            if (!empty($password)) {
                $bizContentArray['digest'] = $this->calculateBodyDigest($customerCode, $password, $privateKey);
            }

            $bizContent = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE);
            $headerDigest = $this->calculateHeaderDigest($bizContent, $privateKey);
            $timestamp = (string) round(microtime(true) * 1000);

            $response = Http::asForm()
                ->withoutVerifying()
                ->withHeaders([
                    'apiAccount'   => $apiAccount,
                    'digest'       => $headerDigest,
                    'timestamp'    => $timestamp,
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                ])
                ->timeout(15)
                ->post("{$baseUrl}/logistics/trace", [
                    'bizContent' => $bizContent,
                ]);

            $resJson = $response->json() ?? [];
            $events = [];
            $currentStatus = 'in_transit';

            if ($response->successful() && !empty($resJson['data'])) {
                $traces = $resJson['data'][0]['details'] ?? $resJson['data']['details'] ?? [];
                foreach ($traces as $trace) {
                    $events[] = [
                        'status'      => $trace['scanType'] ?? 'UPDATE',
                        'time'        => $trace['scanTime'] ?? now()->toIso8601String(),
                        'description' => $trace['desc'] ?? ($trace['scanType'] ?? 'تحديث على الشحنة'),
                    ];
                }

                $lastScan = end($traces);
                if ($lastScan && !empty($lastScan['scanType'])) {
                    $scanType = strtoupper($lastScan['scanType']);
                    if (str_contains($scanType, 'SIGN') || str_contains($scanType, 'DELIVERED')) {
                        $currentStatus = 'delivered';
                    } elseif (str_contains($scanType, 'RETURN')) {
                        $currentStatus = 'returned';
                    }
                }
            }

            return [
                'tracking_number' => $trackingNumber,
                'status'          => $currentStatus,
                'events'          => $events,
                'raw_response'    => $resJson,
            ];
        } catch (\Throwable $e) {
            Log::warning("J&T Express Track Exception [Tracking: {$trackingNumber}]: " . $e->getMessage());

            return [
                'tracking_number' => $trackingNumber,
                'status'          => 'in_transit',
                'events'          => [],
            ];
        }
    }

    /**
     * Cancel shipment in J&T Express
     */
    public function cancelShipment(string $trackingNumber, ShippingGateway $gateway): bool
    {
        $creds = $gateway->credentials ?? [];
        $customerCode = $creds['customer_code'] ?? null;
        $apiAccount   = $creds['api_account'] ?? $creds['api_password'] ?? $creds['api_key'] ?? null;
        $privateKey   = $creds['private_key'] ?? null;
        $password     = $creds['password'] ?? $creds['vip_password'] ?? null;
        $isSandbox    = (bool) ($creds['is_sandbox'] ?? false);

        $baseUrl = $isSandbox ? $this->sandboxBaseUrl : $this->liveBaseUrl;

        if (empty($customerCode) || empty($apiAccount) || empty($privateKey)) {
            return false;
        }

        try {
            $bizContentArray = [
                'customerCode' => (string) $customerCode,
                'txlogisticId' => $trackingNumber,
                'billCode'     => $trackingNumber,
                'reason'       => 'طلب إلغاء من لوحة تحكم المتجر',
            ];

            if (!empty($password)) {
                $bizContentArray['digest'] = $this->calculateBodyDigest($customerCode, $password, $privateKey);
            }

            $bizContent = json_encode($bizContentArray, JSON_UNESCAPED_UNICODE);
            $headerDigest = $this->calculateHeaderDigest($bizContent, $privateKey);
            $timestamp = (string) round(microtime(true) * 1000);

            $response = Http::asForm()
                ->withoutVerifying()
                ->withHeaders([
                    'apiAccount'   => $apiAccount,
                    'digest'       => $headerDigest,
                    'timestamp'    => $timestamp,
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                ])
                ->timeout(15)
                ->post("{$baseUrl}/order/cancelOrder", [
                    'bizContent' => $bizContent,
                ]);

            $resJson = $response->json() ?? [];
            $code = (string) ($resJson['code'] ?? '');

            Log::info("J&T Express Cancel Order [Tracking: {$trackingNumber}]", [
                'response' => $resJson,
            ]);

            return $response->successful() && ($code === '1' || $code === '200' || strtoupper($code) === 'SUCCESS');
        } catch (\Throwable $e) {
            Log::error("J&T Express Cancel Exception [Tracking: {$trackingNumber}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate VIP password hash: strtoupper(md5($password . 'jadada236t2'))
     */
    public function calculatePasswordHash(string $password): string
    {
        return strtoupper(md5($password . 'jadada236t2'));
    }

    /**
     * Calculate internal Body Digest: base64_encode(md5($customerCode . $pwd . $privateKey, true))
     */
    public function calculateBodyDigest(string $customerCode, string $password, string $privateKey): string
    {
        $pwd = $this->calculatePasswordHash($password);
        return base64_encode(md5($customerCode . $pwd . $privateKey, true));
    }

    /**
     * Calculate Header Digest: base64_encode(md5($bizContent . $privateKey, true))
     */
    public function calculateHeaderDigest(string $bizContent, string $privateKey): string
    {
        return base64_encode(md5($bizContent . $privateKey, true));
    }

    /**
     * Format Egyptian phone number to 01xxxxxxxxx
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
