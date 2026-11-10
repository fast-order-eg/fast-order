<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Services\SettingService;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'group',
        'type',
    ];

    /**
     * Get a setting value by key (delegates to SettingService for multi-level fallback)
     */
    public static function get(string $key, $default = null, $tenantId = null)
    {
        return app(SettingService::class)->get($key, $default, $tenantId);
    }

    /**
     * Set a setting value by key (delegates to SettingService for multi-level storage)
     */
    public static function set(string $key, $value, string $group = 'general', $tenantId = null): void
    {
        app(SettingService::class)->set($key, $value, $group, $tenantId);
    }

    /**
     * Get a global platform setting (tenant_id is null)
     */
    public static function getGlobal(string $key, $default = null)
    {
        return app(SettingService::class)->getGlobal($key, $default);
    }

    /**
     * Set a global platform setting (tenant_id is null)
     */
    public static function setGlobal(string $key, $value, string $group = 'general'): void
    {
        app(SettingService::class)->setGlobal($key, $value, $group);
    }

    /**
     * Get the store's custom logo URL if set and existing on disk.
     */
    public static function getStoreLogoUrl(?int $tenantId = null): ?string
    {
        $logo = static::get('logo', null, $tenantId);
        if (!empty($logo)) {
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, 'data:')) {
                return $logo;
            }
            $relPath = ltrim($logo, '/');
            if (str_starts_with($relPath, 'storage/')) {
                $relPath = substr($relPath, 8);
            }
            $fullPath = storage_path('app/public/' . $relPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $relPath);
            }
        }

        $tenant = $tenantId ? \App\Models\Tenant::find($tenantId) : (app()->bound(\App\Models\Tenant::class) ? app(\App\Models\Tenant::class) : null);
        if ($tenant && !empty($tenant->logo)) {
            $logo = $tenant->logo;
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, 'data:')) {
                return $logo;
            }
            $relPath = ltrim($logo, '/');
            if (str_starts_with($relPath, 'storage/')) {
                $relPath = substr($relPath, 8);
            }
            $fullPath = storage_path('app/public/' . $relPath);
            if (file_exists($fullPath)) {
                return asset('storage/' . $relPath);
            }
        }

        return null;
    }

    /**
     * Get the official Fast Order brand fallback logo URL.
     */
    public static function getBrandLogoUrl(): string
    {
        return asset('images/logo.png');
    }

    /**
     * Get the resolved logo URL (store logo if valid, otherwise brand logo).
     */
    public static function getLogoUrl(?int $tenantId = null): string
    {
        return static::getStoreLogoUrl($tenantId) ?: static::getBrandLogoUrl();
    }
}
