<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionApiPixel extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'platform',
        'pixel_id',
        'access_token',
        'test_event_code',
        'note',
        'is_active',
        'events',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'events'    => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
