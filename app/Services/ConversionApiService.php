<?php

namespace App\Services;

use App\Models\ConversionApiPixel;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConversionApiService
{
    /**
     * إرسال حدث الشراء (Purchase) لكل البيكسلات النشطة التابعة لمتجر الأوردر
     */
    public static function sendPurchaseEvent(Order $order, ?string $clientIp = null, ?string $clientUserAgent = null): array
    {
        if (!$order->tenant_id) {
            return ['status' => 'skipped', 'reason' => 'No tenant_id'];
        }

        $pixels = ConversionApiPixel::where('tenant_id', $order->tenant_id)
            ->where('is_active', true)
            ->get();

        if ($pixels->isEmpty()) {
            return ['status' => 'skipped', 'reason' => 'No active CAPI pixels configured'];
        }

        $results = [];
        $eventId = 'ORDER_' . $order->id . '_' . $order->reference_number;
        $ip = $clientIp ?: request()->ip() ?: '127.0.0.1';
        $ua = $clientUserAgent ?: request()->userAgent() ?: 'Mozilla/5.0';

        foreach ($pixels as $pixel) {
            try {
                switch (strtolower($pixel->platform)) {
                    case 'facebook':
                    case 'meta':
                        $results['facebook_' . $pixel->id] = self::sendFacebookEvent($pixel, $order, $eventId, $ip, $ua);
                        break;

                    case 'tiktok':
                        $results['tiktok_' . $pixel->id] = self::sendTikTokEvent($pixel, $order, $eventId, $ip, $ua);
                        break;

                    case 'snapchat':
                        $results['snapchat_' . $pixel->id] = self::sendSnapchatEvent($pixel, $order, $eventId, $ip, $ua);
                        break;

                    default:
                        Log::warning("Unsupported CAPI platform: {$pixel->platform}");
                }
            } catch (\Throwable $e) {
                Log::error("CAPI Send Error for Pixel #{$pixel->id} ({$pixel->platform}): " . $e->getMessage());
                $results['error_' . $pixel->id] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * إرسال حدث تجريبي فوري لاختبار البيكسل والـ Access Token
     */
    public static function sendTestEvent(ConversionApiPixel $pixel): array
    {
        $mockOrder = new Order([
            'id'               => rand(1000, 9999),
            'reference_number' => 'TEST' . rand(10000, 99999),
            'customer_name'    => 'عميل تجريبي FastOrder',
            'customer_phone'   => '01000000000',
            'customer_email'   => 'test@fast-order-eg.tech',
            'governorate'      => 'القاهرة',
            'total'            => 150.00,
            'items'            => [
                [
                    'id'       => 1,
                    'name'     => 'منتج تجريبي',
                    'price'    => 150.00,
                    'quantity' => 1,
                    'total'    => 150.00,
                ]
            ]
        ]);

        $eventId = 'TEST_EVENT_' . time() . '_' . rand(100, 999);
        $ip = request()->ip() ?: '127.0.0.1';
        $ua = request()->userAgent() ?: 'Mozilla/5.0';

        switch (strtolower($pixel->platform)) {
            case 'facebook':
            case 'meta':
                return self::sendFacebookEvent($pixel, $mockOrder, $eventId, $ip, $ua, true);

            case 'tiktok':
                return self::sendTikTokEvent($pixel, $mockOrder, $eventId, $ip, $ua, true);

            case 'snapchat':
                return self::sendSnapchatEvent($pixel, $mockOrder, $eventId, $ip, $ua, true);

            default:
                return [
                    'success' => false,
                    'message' => 'منصة غير مدعومة: ' . $pixel->platform
                ];
        }
    }

    /**
     * إرسال لـ Meta (Facebook) Graph API
     */
    private static function sendFacebookEvent(ConversionApiPixel $pixel, Order $order, string $eventId, string $ip, string $ua, bool $isTest = false): array
    {
        $phoneHash = self::hashPhone($order->customer_phone);
        $nameParts = explode(' ', trim($order->customer_name ?? 'عميل'), 2);
        $fnHash = self::hashString($nameParts[0] ?? '');
        $lnHash = self::hashString($nameParts[1] ?? '');
        $emailHash = !empty($order->customer_email) ? self::hashString($order->customer_email) : null;
        $cityHash = !empty($order->governorate) ? self::hashString($order->governorate) : null;

        $userData = [
            'client_ip_address' => $ip,
            'client_user_agent' => $ua,
            'ph'                => array_filter([$phoneHash]),
            'fn'                => array_filter([$fnHash]),
            'ln'                => array_filter([$lnHash]),
            'country'           => [self::hashString('eg')],
        ];

        if ($emailHash) $userData['em'] = [$emailHash];
        if ($cityHash)  $userData['ct'] = [$cityHash];

        // Custom contents
        $items = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'id'         => (string) ($item['id'] ?? 'item'),
                'quantity'   => (int) ($item['quantity'] ?? $item['qty'] ?? 1),
                'item_price' => (float) ($item['price'] ?? 0),
            ];
        }

        $eventPayload = [
            'event_name'       => 'Purchase',
            'event_time'       => time(),
            'event_id'         => $eventId,
            'event_source_url' => url('/order-success/' . ($order->reference_number ?? 'test')),
            'action_source'    => 'website',
            'user_data'        => $userData,
            'custom_data'      => [
                'currency'     => 'EGP',
                'value'        => (float) $order->total,
                'content_type' => 'product',
                'contents'     => $contents,
                'order_id'     => (string) $order->reference_number,
            ]
        ];

        $payload = [
            'data' => [$eventPayload],
        ];

        $testCode = $pixel->test_event_code;
        if (!empty($testCode)) {
            $payload['test_event_code'] = trim($testCode);
        }

        $url = "https://graph.facebook.com/v19.0/{$pixel->pixel_id}/events";
        $response = Http::timeout(10)->post($url . '?access_token=' . trim($pixel->access_token), $payload);

        $json = $response->json() ?? [];
        $isOk = $response->successful() && isset($json['events_received']) && $json['events_received'] > 0;

        return [
            'success'     => $isOk,
            'status_code' => $response->status(),
            'message'     => $isOk ? 'تم استلام الحدث بنجاح في فيسبوك Meta' : ($json['error']['message'] ?? 'فشل إرسال الحدث'),
            'response'    => $json,
        ];
    }

    /**
     * إرسال لـ TikTok Events API
     */
    private static function sendTikTokEvent(ConversionApiPixel $pixel, Order $order, string $eventId, string $ip, string $ua, bool $isTest = false): array
    {
        $phoneHash = self::hashPhone($order->customer_phone);
        $emailHash = !empty($order->customer_email) ? self::hashString($order->customer_email) : null;

        $user = [
            'ip'         => $ip,
            'user_agent' => $ua,
        ];
        if ($phoneHash) $user['phone_number'] = $phoneHash;
        if ($emailHash) $user['email'] = $emailHash;

        $items = is_array($order->items) ? $order->items : json_decode($order->items ?? '[]', true);
        $contents = [];
        foreach ($items as $item) {
            $contents[] = [
                'content_id'   => (string) ($item['id'] ?? 'item'),
                'content_type' => 'product',
                'content_name' => (string) ($item['name'] ?? 'product'),
                'quantity'     => (int) ($item['quantity'] ?? $item['qty'] ?? 1),
                'price'        => (float) ($item['price'] ?? 0),
            ];
        }

        $eventPayload = [
            'event'       => 'CompletePayment',
            'event_time'  => time(),
            'event_id'    => $eventId,
            'user'        => $user,
            'properties'  => [
                'currency' => 'EGP',
                'value'    => (float) $order->total,
                'contents' => $contents,
            ]
        ];

        $payload = [
            'event_source'    => 'web',
            'event_source_id' => trim($pixel->pixel_id),
            'data'            => [$eventPayload],
        ];

        $testCode = $pixel->test_event_code;
        if (!empty($testCode)) {
            $payload['test_event_code'] = trim($testCode);
        }

        $url = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';
        $response = Http::withHeaders([
            'Access-Token' => trim($pixel->access_token),
            'Content-Type' => 'application/json',
        ])->timeout(10)->post($url, $payload);

        $json = $response->json() ?? [];
        $isOk = $response->successful() && ($json['code'] ?? -1) === 0;

        return [
            'success'     => $isOk,
            'status_code' => $response->status(),
            'message'     => $isOk ? 'تم استلام الحدث بنجاح في TikTok' : ($json['message'] ?? 'فشل إرسال الحدث إلى تيك توك'),
            'response'    => $json,
        ];
    }

    /**
     * إرسال لـ Snapchat Conversions API
     */
    private static function sendSnapchatEvent(ConversionApiPixel $pixel, Order $order, string $eventId, string $ip, string $ua, bool $isTest = false): array
    {
        $phoneHash = self::hashPhone($order->customer_phone);
        $emailHash = !empty($order->customer_email) ? self::hashString($order->customer_email) : null;

        $payload = [
            'pixel_id'              => trim($pixel->pixel_id),
            'event_type'            => 'PURCHASE',
            'event_conversion_type' => 'WEB',
            'timestamp'             => (string) (time() * 1000),
            'event_tag'             => $eventId,
            'price'                 => (float) $order->total,
            'currency'              => 'EGP',
            'ip_address'            => $ip,
            'user_agent'            => $ua,
        ];

        if ($phoneHash) $payload['hashed_phone_number'] = $phoneHash;
        if ($emailHash) $payload['hashed_email'] = $emailHash;

        $url = 'https://tr.snapchat.com/v2/conversion';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . trim($pixel->access_token),
            'Content-Type'  => 'application/json',
        ])->timeout(10)->post($url, $payload);

        $json = $response->json() ?? [];
        $isOk = $response->successful() && (($json['status'] ?? '') === 'SUCCESS' || $response->status() === 200);

        return [
            'success'     => $isOk,
            'status_code' => $response->status(),
            'message'     => $isOk ? 'تم استلام الحدث بنجاح في Snapchat' : ($json['message'] ?? 'فشل إرسال الحدث إلى سناب شات'),
            'response'    => $json,
        ];
    }

    /**
     * تشفير رقم الهاتف بصيغة E.164 الدولية
     */
    private static function hashPhone(?string $phone): ?string
    {
        if (empty($phone)) return null;

        // Clean non-digits
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (empty($digits)) return null;

        // Convert Egyptian numbers to 201xxxxxxxxx
        if (str_starts_with($digits, '01')) {
            $digits = '20' . substr($digits, 1);
        } elseif (str_starts_with($digits, '0020')) {
            $digits = substr($digits, 2);
        } elseif (!str_starts_with($digits, '20') && strlen($digits) === 10) {
            $digits = '20' . $digits;
        }

        return hash('sha256', $digits);
    }

    /**
     * تشفير النصوص (lower-case + trimmed SHA-256)
     */
    private static function hashString(?string $str): ?string
    {
        if (empty($str)) return null;
        $clean = trim(mb_strtolower($str, 'UTF-8'));
        return hash('sha256', $clean);
    }
}
