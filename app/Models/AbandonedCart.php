<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class AbandonedCart extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'email',
        'phone',
        'customer_name',
        'governorate',
        'customer_address',
        'session_id',
        'cart_data',
        'subtotal',
        'total',
        'status',
        'converted_order_id',
        'recovery_token',
        'recovery_email_sent_at',
        'recovered_at',
        'last_contacted_at',
        'notes',
    ];

    protected $casts = [
        'cart_data' => 'array',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'recovery_email_sent_at' => 'datetime',
        'recovered_at' => 'datetime',
        'last_contacted_at' => 'datetime',
    ];

    protected $attributes = [
        'cart_data' => '[]',
        'status' => 'abandoned',
        'subtotal' => 0,
        'total' => 0,
    ];

    /**
     * Tenant relationship
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that owns the abandoned cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The order created when this abandoned cart was converted.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    /**
     * Scope for carts that have not yet been converted
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'converted')->whereNull('recovered_at');
    }

    /**
     * Scope for converted carts
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted')->orWhereNotNull('recovered_at');
    }
}
