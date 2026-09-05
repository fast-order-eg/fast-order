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

            // 3. Sender Info (Store / Merchant) - Pulled dynamically from store settings
            $tenantId = $order->tenant_id;
            $tenant = $order->tenant;
            $storeName = Setting::get('store_name', null, $tenantId) ?: ($tenant?->name ?? 'متجر إلكتروني');
            $rawStorePhone = Setting::get('phone', null, $tenantId) ?: ($tenant?->owner?->phone ?? '01000000000');
            $storePhone = $this->formatPhone($rawStorePhone);
            
            $prefProv = Setting::get('sender_governorate', null, $tenantId);
            $prefCity = Setting::get('sender_city', null, $tenantId);
            $rawAddress = Setting::get('address', null, $tenantId) ?: Setting::get('pickup_address', null, $tenantId);
            $senderLoc = $this->parseStoreLocation($rawAddress, $prefProv, $prefCity);

            // 4. Receiver Info (Customer)
            $receiverPhone = $this->formatPhone($order->customer_phone);
            $receiverProv  = $order->governorate ?: 'القاهرة';
            $receiverCity  = $order->city ?: $receiverProv;
            $receiverAddr  = $order->customer_address ?: ($order->shipping_address ?: 'العنوان بالتفصيل');
            $receiverStreet = trim(str_replace([$receiverProv, $receiverCity], '', $receiverAddr), " -–—,،|/ \t\n\r\0\x0B");
            if (empty($receiverStreet)) {
                $receiverStreet = $receiverAddr;
            }

            // 5. Unique Transaction Logistic ID
            $txlogisticId = 'ORD_' . $order->id . '_' . $order->reference_number;

            // 6. Calculate Body Digest
            $bodyDigest = $this->calculateBodyDigest($customerCode, $password, $privateKey);

            // 7. Build Business Content Payload
            $bizContentArray = [
                'customerCode'         => (string) $customerCode,
                'digest'               => $bodyDigest,
                'serviceType'          => '01',
                'orderType'            => '2', // 2: Monthly settlement contract client
                'deliveryType'         => '04',
                'operateType'          => '1', // 1: Add new order
                'expressType'          => 'EZ',
                'payType'              => $payType,
                'priceCurrency'        => 'EGP',
                'txlogisticId'         => $txlogisticId,
                'goodsType'            => 'ITN1',
                'totalQuantity'        => 1,   // J&T requirement: exactly 1 parcel per waybill
                'itemsValue'           => (float) $order->total,
                'collectionOnDelivery' => $codAmount,
                'sender' => [
                    'name'    => $storeName,
                    'mobile'  => $storePhone,
                    'phone'   => '', // Left empty to avoid duplicate phone display
                    'prov'    => $senderLoc['prov'],
                    'city'    => $senderLoc['city'],
                    'street'  => $senderLoc['street'],
                    'address' => $senderLoc['address'],
                ],
                'receiver' => [
                    'name'    => $order->customer_name ?: 'عميل',
                    'mobile'  => $receiverPhone,
                    'phone'   => '',
                    'prov'    => $receiverProv,
                    'city'    => $receiverCity,
                    'street'  => mb_substr($receiverStreet, 0, 100),
                    'address' => mb_substr($receiverAddr, 0, 150),
                ],
                'items'  => $items,
                'remark' => $this->buildOrderRemark($order, $rawItems),
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

                $airwayBillUrl = "https://www.jtjms-eg.com/track?bills={$trackingNumber}";

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
    public function cancelShipment(string $trackingNumber, ShippingGateway $gateway, ?string $txlogisticId = null): bool
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

        // If txlogisticId is not provided, look up shipment and order
        if (empty($txlogisticId)) {
            $shipment = \App\Models\Shipment::withoutGlobalScopes()
                ->where('tracking_number', $trackingNumber)
                ->first();
            if ($shipment && $shipment->order) {
                $txlogisticId = 'ORD_' . $shipment->order->id . '_' . $shipment->order->reference_number;
            } else {
                $txlogisticId = $trackingNumber;
            }
        }

        try {
            $bizContentArray = [
                'customerCode' => (string) $customerCode,
                'txlogisticId' => (string) $txlogisticId,
                'billCode'     => (string) $trackingNumber,
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

            Log::info("J&T Express Cancel Order [BillCode: {$trackingNumber}, txlogisticId: {$txlogisticId}]", [
                'response' => $resJson,
            ]);

            return $response->successful() && ($code === '1' || $code === '200' || strtoupper($code) === 'SUCCESS');
        } catch (\Throwable $e) {
            Log::error("J&T Express Cancel Exception [Tracking: {$trackingNumber}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse store location into prov, city, street without redundant text.
     */
    protected function parseStoreLocation(?string $address, ?string $prefProv = null, ?string $prefCity = null): array
    {
        $govList = [
            'القاهرة', 'الجيزة', 'الإسكندرية', 'الدقهلية', 'الغربية', 'الشرقية', 'المنوفية', 
            'القليوبية', 'البحيرة', 'كفر الشيخ', 'دمياط', 'بورسعيد', 'الإسماعيلية', 'السويس', 
            'الفيوم', 'بني سويف', 'المنيا', 'أسيوط', 'سوهاج', 'قنا', 'الأقصر', 'أسوان', 
            'البحر الأحمر', 'الوادي الجديد', 'مطروح', 'شمال سيناء', 'جنوب سيناء'
        ];

        $prov = $prefProv ?: 'الدقهلية';
        $city = $prefCity ?: 'المنصورة';
        $fullAddr = trim($address ?? '');

        if (!empty($fullAddr)) {
            foreach ($govList as $gov) {
                if (mb_stripos($fullAddr, $gov) !== false) {
                    $prov = $gov;
                    break;
                }
            }
        }

        $parts = preg_split('/[-–—,،|\/]+/', $fullAddr);
        $parts = array_values(array_filter(array_map('trim', $parts)));

        if (count($parts) >= 3) {
            if (mb_stripos($parts[0], $prov) !== false) {
                $city = $parts[1];
                $streetParts = array_slice($parts, 2);
            } else {
                $streetParts = $parts;
            }
        } elseif (count($parts) === 2) {
            $city = $parts[0];
            $streetParts = array_slice($parts, 1);
        } else {
            $streetParts = $parts;
        }

        $street = !empty($streetParts) ? implode(' - ', $streetParts) : ($fullAddr ?: 'المقر الرئيسي');
        $cleanStreet = trim(str_replace([$prov, $city], '', $street), " -–—,،|/ \t\n\r\0\x0B");

        if (empty($cleanStreet)) {
            $cleanStreet = $fullAddr ?: ($prov . ' - ' . $city);
        }

        return [
            'prov'    => $prov,
            'city'    => $city,
            'street'  => mb_substr($cleanStreet, 0, 100),
            'address' => mb_substr($fullAddr ?: ($prov . ' - ' . $city), 0, 150),
        ];
    }

    /**
     * Build clean remark for shipping carrier with product options and sanitized customer note.
     */
    protected function buildOrderRemark(Order $order, array $rawItems): string
    {
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
            if (!empty($item['options']) && is_array($item['options'])) {
                foreach ($item['options'] as $k => $v) {
                    if ($v) {
                        $opts[] = "{$k}: {$v}";
                    }
                }
            }

            $optStr = !empty($opts) ? ' (' . implode(', ', $opts) . ')' : '';
            $itemDescriptions[] = "{$name} x{$qty}{$optStr}";
        }

        $itemsText = implode(' | ', $itemDescriptions);

        // Sanitize customer notes: strip any line containing [واتساب] or [whatsapp]
        $cleanNotes = '';
        if (!empty($order->notes)) {
            $lines = preg_split("/\r\n|\n|\r/", $order->notes);
            $valid = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed)) continue;
                if (str_contains($trimmed, 'واتساب') || str_contains($trimmed, 'whatsapp') || str_contains($trimmed, 'WhatsApp')) {
                    continue;
                }
                $valid[] = $trimmed;
            }
            $cleanNotes = implode(' - ', $valid);
        }

        $remark = $itemsText;
        if (!empty($cleanNotes)) {
            $remark .= ' | ملاحظة: ' . $cleanNotes;
        }

        if (empty(trim($remark))) {
            $remark = "طلب رقم #{$order->reference_number}";
        }

        return mb_substr($remark, 0, 200);
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
