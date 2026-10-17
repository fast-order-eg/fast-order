<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdCampaignReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'campaign_ids',
        'token',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'campaign_ids' => 'array',
        'is_active'    => 'boolean',
    ];

    /**
     * Get the user who created this report.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get clean array of string campaign IDs.
     */
    public function getCleanCampaignIds(): array
    {
        $raw = $this->campaign_ids;
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $cleaned = [];
        foreach ($raw as $id) {
            $val = trim((string) $id);
            if (!empty($val) && !in_array($val, $cleaned, true)) {
                $cleaned[] = $val;
            }
        }

        return $cleaned;
    }

    /**
     * Generate the full public URL for this report.
     */
    public function getPublicUrl(): string
    {
        $appUrl = config('app.url') ?: 'https://app.fast-order-eg.tech';
        return rtrim($appUrl, '/') . '/ad-reports/' . $this->token;
    }
}
