<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class Cart extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'session_id'];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(CartItem::class)->where('saved_for_later', false);
    }

    public function savedItems(): HasMany
    {
        return $this->hasMany(CartItem::class)->where('saved_for_later', true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->activeItems->sum(fn($item) => $item->price * $item->quantity);
    }

    public function getItemsCountAttribute(): int
    {
        return $this->activeItems->sum('quantity');
    }
}
