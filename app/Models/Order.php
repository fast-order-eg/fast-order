<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToTenant;

class Order extends Model
{
    use HasFactory, BelongsToTenant;
    
    protected $fillable = [
        'tenant_id',
        'user_id',
        'reference_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'governorate',
        'payment_method',
        'shipping_cost',
        'coupon_code',
        'discount',
        'items',
        'subtotal',
        'total',
        'status',
        'is_printed',
        'printed_at',
        'is_unlocked',
        'unlocked_at',
        'notes'
    ];

    protected $casts = [
        'items' => 'array',
        'shipping_cost' => 'integer',
        'discount' => 'float',
        'subtotal' => 'integer',
        'total' => 'integer',
        'is_printed' => 'boolean',
        'printed_at' => 'datetime',
        'is_unlocked' => 'boolean',
        'unlocked_at' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * تحديد التوقيت للنموذج
     */
    protected $dateFormat = 'Y-m-d H:i:s';
    
    /**
     * Get the attributes that should be cast to dates.
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'shipping_cost' => 'integer',
            'discount' => 'float',
            'subtotal' => 'integer',
            'total' => 'integer',
            'is_printed' => 'boolean',
            'printed_at' => 'datetime',
            'is_unlocked' => 'boolean',
            'unlocked_at' => 'datetime',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * توليد رقم مرجعي عشوائي فريد مكون من 5 أرقام
     */
    public static function generateReferenceNumber()
    {
        do {
            $referenceNumber = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('reference_number', $referenceNumber)->exists());
        
        return $referenceNumber;
    }

    /**
     * إنشاء طلب جديد مع رقم مرجعي
     */
    public static function createWithReference(array $data)
    {
        $data['reference_number'] = self::generateReferenceNumber();
        return self::create($data);
    }

    protected static function booted()
    {
        static::created(function ($order) {
            if ($order->tenant_id) {
                try {
                    $tenant = \App\Models\Tenant::find($order->tenant_id);
                    if ($tenant) {
                        if ($tenant->isCommissionPlan()) {
                            // Commission Plan: Deduct 2 EGP per new order if wallet balance is available
                            if (($tenant->wallet_balance ?? 0) >= 2) {
                                $tenant->decrement('wallet_balance', 2);
                                \App\Models\WalletTransaction::create([
                                    'tenant_id'   => $tenant->id,
                                    'amount'      => 2,
                                    'type'        => 'debit',
                                    'description' => 'رسوم الطلب رقم (' . $order->reference_number . ')',
                                ]);
                                static::where('id', $order->id)->update([
                                    'is_unlocked' => true,
                                    'unlocked_at' => now(),
                                ]);
                            } else {
                                // Insufficient wallet balance -> Keep new order LOCKED until merchant tops up wallet
                                static::where('id', $order->id)->update([
                                    'is_unlocked' => false,
                                ]);
                            }
                        } else {
                            // Non-commission Plan (Free / Monthly / Yearly): All new orders are 100% UNLOCKED & FREE
                            static::where('id', $order->id)->update([
                                'is_unlocked' => true,
                                'unlocked_at' => now(),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Order auto unlock fee failed: ' . $e->getMessage());
                }
            }
        });

        static::saved(function ($order) {
            if ($order->tenant_id) {
                \App\Services\CacheService::invalidateDashboardStats($order->tenant_id);
            }
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        });

        static::deleted(function ($order) {
            if ($order->tenant_id) {
                \App\Services\CacheService::invalidateDashboardStats($order->tenant_id);
            }
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        });
    }

    /**
     * الحفاظ على التوقيت المحلي لمصر عند التسلسل لـ JSON
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * حساب إجمالي عدد القطع في الطلب
     */
    public function getTotalItemsAttribute()
    {
        return collect($this->items)->sum('quantity');
    }
}
