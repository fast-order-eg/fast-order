<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class PaymentGateway extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'provider',
        'is_active',
        'display_name',
        'display_description',
        'sort_order',
        'credentials',
        'settings',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
        'credentials' => 'array',
        'settings'    => 'array',
    ];

    /**
     * Scope for active gateways sorted by sort_order
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }
}
