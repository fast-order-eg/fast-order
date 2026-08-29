<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getCart(?int $tenantId): Cart
    {
        $tenantId = $tenantId ?? session()->get('tenant_id') ?? config('tenant.id') ?? (auth()->check() ? auth()->user()->tenant_id : null);
        
        if (!$tenantId) {
            $tenantId = 1; // Fallback safe default value for environments without request context
        }

        if (Auth::check()) {
            $cart = Cart::firstOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => Auth::id()],
                ['session_id' => Session::getId()]
            );
            // Merge guest cart if exists
            $this->mergeGuestCart($cart, $tenantId);
            return $cart;
        }

        return Cart::firstOrCreate(
            ['tenant_id' => $tenantId, 'session_id' => Session::getId(), 'user_id' => null]
        );
    }

    public function addItem(Cart $cart, int $productId, int $quantity = 1, ?int $variantId = null, array $options = []): CartItem
    {
        $product = \App\Models\Product::findOrFail($productId);
        
        $size = $options['size'] ?? null;
        $color = $options['color'] ?? null;
        $customOpts = array_filter($options, fn($k) => !in_array($k, ['size', 'color']), ARRAY_FILTER_USE_KEY);

        $price = $product->getVariantPrice($size, $color, $customOpts);

        $existingItem = $cart->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->where('saved_for_later', false)
            ->get()
            ->first(function($item) use ($options) {
                $itemOpts = $item->options ?? [];
                $reqOpts  = $options ?? [];
                ksort($itemOpts);
                ksort($reqOpts);
                return $itemOpts === $reqOpts;
            });

        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            $existingItem->update(['price' => $price]);
            return $existingItem->fresh();
        }

        return $cart->items()->create([
            'product_id'         => $productId,
            'product_variant_id' => $variantId,
            'quantity'           => $quantity,
            'price'              => $price,
            'options'            => $options ?: null,
        ]);
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }
        return $item;
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function saveForLater(CartItem $item): void
    {
        $item->update(['saved_for_later' => true]);
    }

    public function moveToCart(CartItem $item): void
    {
        $item->update(['saved_for_later' => false]);
    }

    public function applyCoupon(Cart $cart, string $code, int $tenantId): array
    {
        $coupon = Coupon::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$coupon) {
            return ['success' => false, 'message' => 'كود الخصم غير صحيح أو منتهي'];
        }

        $subtotal = $cart->subtotal;
        $discount = $coupon->type === 'percentage'
            ? ($subtotal * $coupon->value / 100)
            : (float) $coupon->value;

        $finalDiscount = min($discount, $subtotal);

        Session::put('coupon_' . $cart->id, [
            'code'     => $code,
            'discount' => $finalDiscount,
            'type'     => $coupon->type,
            'value'    => $coupon->value,
        ]);

        return [
            'success'  => true,
            'discount' => $finalDiscount,
            'message'  => 'تم تطبيق الكود بنجاح',
        ];
    }

    public function getCoupon(Cart $cart): ?array
    {
        return Session::get('coupon_' . $cart->id);
    }

    public function removeCoupon(Cart $cart): void
    {
        Session::forget('coupon_' . $cart->id);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $this->removeCoupon($cart);
    }

    public function getCartSummary(Cart $cart): array
    {
        $cart->load('activeItems');
        $subtotal = $cart->subtotal;
        $coupon   = $this->getCoupon($cart);
        $discount = $coupon['discount'] ?? 0;
        $total    = max(0, $subtotal - $discount);

        return [
            'subtotal'    => $subtotal,
            'discount'    => $discount,
            'coupon'      => $coupon,
            'shipping'    => 0,
            'total'       => $total,
            'items_count' => $cart->items_count,
        ];
    }

    private function mergeGuestCart(Cart $userCart, int $tenantId): void
    {
        $guestCart = Cart::where('tenant_id', $tenantId)
            ->where('session_id', Session::getId())
            ->whereNull('user_id')
            ->first();

        if (!$guestCart) {
            return;
        }

        foreach ($guestCart->activeItems as $guestItem) {
            $this->addItem(
                $userCart,
                $guestItem->product_id,
                $guestItem->quantity,
                $guestItem->product_variant_id,
                $guestItem->options ?? []
            );
        }

        $guestCart->delete();
    }
}
