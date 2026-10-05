<?php

namespace App\Services\Shipping\Speedaf;

use phpseclib3\Crypt\DES;
use RuntimeException;

class SpeedafCipherService
{
    /**
     * Fixed IV vector bytes defined by Speedaf Open API:
     * 0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF
     */
    protected const IV_BYTES = [0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF];

    /**
     * Get current timestamp in milliseconds
     */
    public static function getTimestamp(): string
    {
        return (string) round(microtime(true) * 1000);
    }

    /**
     * Compute MD5 signature: sign = md5(timestamp + secretKey + data)
     */
    public static function sign(string $timestamp, string $secretKey, string $data): string
    {
        return md5($timestamp . $secretKey . $data);
    }

    /**
     * Get configured DES instance
     */
    protected static function getDesInstance(string $secretKey): DES
    {
        $iv = pack('C*', ...self::IV_BYTES);
        $des = new DES('cbc');
        $des->setKey($secretKey);
        $des->setIV($iv);
        return $des;
    }

    /**
     * Encrypt business data array into Speedaf raw encrypted Base64 body
     *
     * @param array|string $data Business request data
     * @param string $secretKey Speedaf Secret Key
     * @param string|null $timestamp Optional millisecond timestamp
     * @return array [ 'body' => string, 'timestamp' => string ]
     */
    public static function encryptPayload(array|string $data, string $secretKey, ?string $timestamp = null): array
    {
        $timestamp = $timestamp ?: self::getTimestamp();
        
        $dataString = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sign = self::sign($timestamp, $secretKey, $dataString);

        $bodyJson = json_encode([
            'data' => is_string($data) ? $data : $data, // preserve object/string structure
            'sign' => $sign,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $des = self::getDesInstance($secretKey);
        $encryptedBytes = $des->encrypt($bodyJson);

        if ($encryptedBytes === false) {
            throw new RuntimeException('فشل تشفير بيانات الطلب لشركة Speedaf.');
        }

        return [
            'body'      => base64_encode($encryptedBytes),
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Decrypt Speedaf encrypted Base64 response data
     *
     * @param string $encryptedBase64
     * @param string $secretKey
     * @return mixed Decoded JSON or raw string
     */
    public static function decryptResponse(string $encryptedBase64, string $secretKey): mixed
    {
        $encryptedBytes = base64_decode($encryptedBase64, true);
        if ($encryptedBytes === false) {
            throw new RuntimeException('فشل فك ترميز Base64 لبيانات رد Speedaf.');
        }

        $des = self::getDesInstance($secretKey);
        $decryptedJson = $des->decrypt($encryptedBytes);

        if ($decryptedJson === false || $decryptedJson === '') {
            throw new RuntimeException('فشل فك تشفير استجابة شركة Speedaf.');
        }

        $decoded = json_decode($decryptedJson, true);
        return $decoded !== null ? $decoded : $decryptedJson;
    }
}
