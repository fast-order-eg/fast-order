<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tutorial extends Model
{
    protected $fillable = [
        'title',
        'category',
        'youtube_url',
        'youtube_id',
        'description',
        'duration',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($tutorial) {
            if ($tutorial->youtube_url) {
                $tutorial->youtube_id = static::extractYoutubeId($tutorial->youtube_url);
            }
        });
    }

    /**
     * Helper to extract YouTube video ID from various link formats
     */
    public static function extractYoutubeId(string $url): ?string
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        // Fallback if raw 11-char ID entered
        if (strlen(trim($url)) === 11 && !str_contains($url, '/')) {
            return trim($url);
        }
        return null;
    }

    /**
     * Get YouTube Embed URL
     */
    public function getEmbedUrlAttribute(): string
    {
        return "https://www.youtube.com/embed/" . ($this->youtube_id ?: 'default');
    }
}
