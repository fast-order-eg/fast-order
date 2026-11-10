<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class BlacklistRecord extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'type', // 'ip', 'phone', 'email'
        'value',
        'reason',
        'is_global',
        'is_whitelisted',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_whitelisted' => 'boolean',
    ];

    /**
     * Scope query to find specific type of record.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope query to global records.
     */
    public function scopeGlobalOnly($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope query to whitelisted records.
     */
    public function scopeWhitelisted($query)
    {
        return $query->where('is_whitelisted', true);
    }
}
