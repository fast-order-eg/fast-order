<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontCartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $cart   = $this->cartService->getCart($tenant->id);

        // Synchronize local storage / guest items if provided
        if ($request->filled('sync_items')) {
            $this->syncLocalItems($request->input('sync_items'), $cart, false);
        }
        if ($request->filled('sync_saved_items')) {
            $this->syncLocalItems($request->input('sync_saved_items'), $cart, true);
        }
        if ($request->filled('sync_items') || $request->filled('sync_saved_items')) {
            $cart->refresh();
        }

        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        $items = $cart->activeItems()->with('product')->get()->map(fn($item) => [
            'id'            => $item->id,
            'product_id'    => $item->product_id,
            'name'          => $item->product?->name ?? 'منتج محذوف',
            'image'         => $item->product?->main_image_path
                ? asset('storage/' . $item->product->main_image_path)
                : ($item->product?->image_url ?? null),
            'price'         => (float) $item->price,
            'quantity'      => $item->quantity,
            'total'         => $item->total,
            'variant'       => null,
            'selectedSize'  => $item->options['size'] ?? null,
            'selectedColor' => $item->options['color'] ?? null,
        ]);

        $savedItems = $cart->savedItems()->with('product')->get()->map(fn($item) => [
            'id'            => $item->id,
            'product_id'    => $item->product_id,
            'name'          => $item->product?->name ?? 'منتج محذوف',
            'image'         => $item->product?->main_image_path
                ? asset('storage/' . $item->product->main_image_path)
                : ($item->product?->image_url ?? null),
            'price'         => (float) $item->price,
            'quantity'      => $item->quantity,
            'total'         => $item->total,
            'selectedSize'  => $item->options['size'] ?? null,
            'selectedColor' => $item->options['color'] ?? null,
        ]);


        // Get cross-sell recommendations for products in the cart
        $productIds = $items->pluck('product_id')->filter()->all();
        $crossSells = \App\Models\ProductRecommendation::whereIn('product_id', $productIds)
            ->where('type', 'cross-sell')
            ->with('recommendedProduct')
            ->get()
            ->map(fn($rec) => $rec->recommendedProduct)
            ->filter(fn($prod) => $prod && !in_array($prod->id, $productIds) && $prod->stock > 0)
            ->unique('id')
            ->values()
            ->map(fn($prod) => [
                'id'           => $prod->id,
                'name'         => $prod->name,
                'price_before' => $prod->price_before,
                'price_after'  => $prod->price_after ?? $prod->price,
                'stock'        => $prod->stock,
                'image_url'    => $prod->main_image_path ? asset('storage/' . $prod->main_image_path) : ($prod->image_url ?: null),
            ]);

        return response()->json([
            'success'     => true,
            'items'       => $items,
            'saved_items' => $savedItems,
            'summary'     => $summary,
            'cross_sells' => $crossSells,
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'integer|min:1|max:100',
            'variant_id' => 'nullable|integer',
        ]);

        $tenant = $request->attributes->get('tenant');
        $cart   = $this->cartService->getCart($tenant->id);

        if ($request->filled('sync_items')) {
            $this->syncLocalItems($request->input('sync_items'), $cart, false);
        }

        $this->cartService->addItem(
            $cart,
            (int) $request->product_id,
            (int) ($request->quantity ?? 1),
            $request->variant_id ? (int) $request->variant_id : null
        );

        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json([
            'success'     => true,
            'message'     => 'تمت إضافة المنتج للسلة',
            'items_count' => $summary['items_count'],
            'summary'     => $summary,
        ]);
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        $request->validate(['quantity' => 'required|integer|min:0|max:100']);

        $tenant = $request->attributes->get('tenant');
        $cart   = $this->cartService->getCart($tenant->id);

        abort_unless($item->cart_id === $cart->id, 403);

        $this->cartService->updateItem($item, (int) $request->quantity);
        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    public function remove(Request $request, CartItem $item): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $cart   = $this->cartService->getCart($tenant->id);

        abort_unless($item->cart_id === $cart->id, 403);

        $this->cartService->removeItem($item);
        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    public function saveForLater(Request $request, CartItem $item): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $cart   = $this->cartService->getCart($tenant->id);

        abort_unless($item->cart_id === $cart->id, 403);

        $this->cartService->saveForLater($item);
        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json(['success' => true, 'message' => 'تم الحفظ لوقت لاحق', 'summary' => $summary]);
    }

    public function moveToCart(Request $request, CartItem $item): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $cart   = $this->cartService->getCart($tenant->id);

        abort_unless($item->cart_id === $cart->id, 403);

        $this->cartService->moveToCart($item);
        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json(['success' => true, 'message' => 'تمت الإضافة للسلة', 'summary' => $summary]);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        $tenant  = $request->attributes->get('tenant');
        $cart    = $this->cartService->getCart($tenant->id);
        $result  = $this->cartService->applyCoupon($cart, $request->code, $tenant->id);
        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json(array_merge($result, ['summary' => $summary]));
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $tenant  = $request->attributes->get('tenant');
        $cart    = $this->cartService->getCart($tenant->id);

        $this->cartService->removeCoupon($cart);
        $summary = $this->enrichSummary($this->cartService->getCartSummary($cart), $tenant);

        return response()->json(['success' => true, 'summary' => $summary]);
    }

    private function enrichSummary(array $summary, $tenant): array
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : json_decode($tenant->settings ?? '{}', true);
        $taxRate  = (float) ($settings['tax_rate'] ?? $settings['tax'] ?? 0);
        $subtotalAfterDiscount = max(0, $summary['subtotal'] - ($summary['discount'] ?? 0));
        $taxAmount = round($subtotalAfterDiscount * ($taxRate / 100), 2);

        $summary['tax']      = $taxAmount;
        $summary['tax_rate'] = $taxRate;
        $summary['total']    = round($subtotalAfterDiscount + $taxAmount + ($summary['shipping'] ?? 0), 2);

        return $summary;
    }

    private function syncLocalItems($itemsInput, $cart, bool $saveForLater = false): void
    {
        $items = is_string($itemsInput) ? json_decode($itemsInput, true) : $itemsInput;
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $lItem) {
            $prodId = (int) ($lItem['product_id'] ?? $lItem['id'] ?? 0);
            $qty    = (int) ($lItem['qty'] ?? $lItem['quantity'] ?? 1);
            $varId  = isset($lItem['variant_id']) && $lItem['variant_id'] ? (int) $lItem['variant_id'] : null;
            
            $options = [];
            if (isset($lItem['selectedSize']) && $lItem['selectedSize']) {
                $options['size'] = $lItem['selectedSize'];
            } elseif (isset($lItem['size']) && $lItem['size']) {
                $options['size'] = $lItem['size'];
            }
            if (isset($lItem['selectedColor']) && $lItem['selectedColor']) {
                $options['color'] = $lItem['selectedColor'];
            } elseif (isset($lItem['color']) && $lItem['color']) {
                $options['color'] = $lItem['color'];
            }
            if (isset($lItem['options']) && is_array($lItem['options'])) {
                foreach ($lItem['options'] as $ok => $ov) {
                    if (!isset($options[$ok])) {
                        $options[$ok] = $ov;
                    }
                }
            }

            if ($prodId > 0 && $qty > 0) {
                try {
                    $item = $this->cartService->addItem($cart, $prodId, $qty, $varId, $options);
                    if ($saveForLater && $item) {
                        $this->cartService->saveForLater($item);
                    }
                } catch (\Exception $e) {
                    // Ignore products that no longer exist or are out of stock during sync
                }
            }
        }
    }

}

