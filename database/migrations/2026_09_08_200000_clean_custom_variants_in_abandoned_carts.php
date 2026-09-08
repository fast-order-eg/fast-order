<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AbandonedCart;
use App\Models\Product;

return new class extends Migration
{
    public function up(): void
    {
        try {
            $carts = AbandonedCart::all();
            foreach ($carts as $cart) {
                $cartData = $cart->cart_data;
                if (empty($cartData['items']) || !is_array($cartData['items'])) continue;
                $changed = false;
                $prodIds = array_filter(array_map(fn($i) => $i['product_id'] ?? ($i['id'] ?? null), $cartData['items']));
                $products = !empty($prodIds)
                    ? Product::where('tenant_id', $cart->tenant_id)->whereIn('id', $prodIds)->get()->keyBy('id')
                    : collect();

                foreach ($cartData['items'] as &$it) {
                    if (!empty($it['options']) && is_array($it['options'])) {
                        $cleanOpts = [];
                        $pId = $it['product_id'] ?? ($it['id'] ?? null);
                        $prod = $products[$pId] ?? null;
                        $cvMap = [];
                        if ($prod && !empty($prod->custom_variants) && is_array($prod->custom_variants)) {
                            foreach ($prod->custom_variants as $idx => $cvItem) {
                                if (!empty($cvItem['name'])) $cvMap[$idx] = $cvItem['name'];
                            }
                        }
                        foreach ($it['options'] as $ok => $ov) {
                            if (str_starts_with((string)$ok, 'product_cv_')) {
                                $changed = true;
                                if (preg_match('/product_cv_(\d+)_/', (string)$ok, $m) && isset($cvMap[(int)$m[1]])) {
                                    $cleanOpts[$cvMap[(int)$m[1]]] = $ov;
                                } else {
                                    $cleanOpts['خيار إضافي'] = $ov;
                                }
                            } else {
                                $cleanOpts[$ok] = $ov;
                            }
                        }
                        $it['options'] = $cleanOpts;
                    }
                }
                unset($it);
                if ($changed) {
                    $cart->cart_data = $cartData;
                    $cart->save();
                }
            }
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
    }
};
