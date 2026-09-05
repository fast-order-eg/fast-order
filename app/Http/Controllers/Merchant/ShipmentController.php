<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ShipmentController extends Controller
{
    /**
     * Create a shipment for an order using the selected provider.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'provider' => ['required', 'string', 'in:bosta,jnt,aramex'],
        ]);

        try {
            $shippingManager = new ShippingManager();
            $shipment = $shippingManager->createShipment($order, $request->provider);

            // Automatically update order status to shipped
            $order->update(['status' => 'shipped']);

            return redirect()->back()->with('success', "تم إنشاء الشحنة بنجاح عبر شركة ({$request->provider}) برقم تتبع: {$shipment->tracking_number} ✓");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'فشل إنشاء الشحنة: ' . $e->getMessage());
        }
    }

    /**
     * Get live tracking info for a shipment.
     */
    public function track(Shipment $shipment): JsonResponse
    {
        $shippingManager = new ShippingManager();
        $driver = $shippingManager->driver($shipment->provider);

        $gateway = \App\Models\ShippingGateway::where('provider', $shipment->provider)->first();
        if (!$gateway) {
            $gateway = new \App\Models\ShippingGateway(['credentials' => ['api_key' => 'test_mode']]);
        }

        $trackingData = $driver->trackShipment($shipment->tracking_number, $gateway);

        return response()->json($trackingData);
    }

    /**
     * Cancel a shipment.
     */
    public function cancel(Shipment $shipment): RedirectResponse
    {
        try {
            $shippingManager = new ShippingManager();
            $shippingManager->cancelShipment($shipment);

            return redirect()->back()->with('success', 'تم إلغاء الشحنة مع شركة الشحن بنجاح ✓');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'فشل إلغاء الشحنة: ' . $e->getMessage());
        }
    }
}
