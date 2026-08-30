<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ConversionApiPixel;
use App\Services\ConversionApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversionApiController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = optional($request->attributes->get('tenant'))->id ?? auth()->user()?->tenant_id;

        $pixels = ConversionApiPixel::where('tenant_id', $tenantId)
            ->latest('id')
            ->get();

        return Inertia::render('Merchant/ConversionApi/Index', [
            'pixels' => $pixels,
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = optional($request->attributes->get('tenant'))->id ?? auth()->user()?->tenant_id;

        $validated = $request->validate([
            'platform'        => 'required|string|in:facebook,meta,tiktok,snapchat',
            'pixel_id'        => 'required|string|max:100',
            'access_token'    => 'required|string|max:2000',
            'test_event_code' => 'nullable|string|max:100',
            'note'            => 'nullable|string|max:255',
            'is_active'       => 'boolean',
        ], [
            'platform.required'     => 'يرجى اختيار نوع المنصة',
            'pixel_id.required'     => 'معرّف البيكسل (Pixel ID) مطلوب',
            'access_token.required' => 'رمز الوصول (Access Token) مطلوب',
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['platform'] = strtolower($validated['platform']);
        if ($validated['platform'] === 'meta') {
            $validated['platform'] = 'facebook';
        }
        $validated['is_active'] = $request->input('is_active', true);

        ConversionApiPixel::create($validated);

        return redirect()->back()->with('success', 'تمت إضافة بيكسل الـ Conversion API بنجاح ✓');
    }

    public function update(Request $request, ConversionApiPixel $conversionApi)
    {
        $tenantId = optional($request->attributes->get('tenant'))->id ?? auth()->user()?->tenant_id;
        if ($conversionApi->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'platform'        => 'required|string|in:facebook,meta,tiktok,snapchat',
            'pixel_id'        => 'required|string|max:100',
            'access_token'    => 'required|string|max:2000',
            'test_event_code' => 'nullable|string|max:100',
            'note'            => 'nullable|string|max:255',
            'is_active'       => 'boolean',
        ]);

        $validated['platform'] = strtolower($validated['platform']);
        if ($validated['platform'] === 'meta') {
            $validated['platform'] = 'facebook';
        }

        $conversionApi->update($validated);

        return redirect()->back()->with('success', 'تم تحديث بيانات البيكسل بنجاح ✓');
    }

    public function destroy(Request $request, ConversionApiPixel $conversionApi)
    {
        $tenantId = optional($request->attributes->get('tenant'))->id ?? auth()->user()?->tenant_id;
        if ($conversionApi->tenant_id !== $tenantId) {
            abort(403);
        }

        $conversionApi->delete();

        return redirect()->back()->with('success', 'تم حذف البيكسل بنجاح ✓');
    }

    public function toggle(Request $request, ConversionApiPixel $conversionApi)
    {
        $tenantId = optional($request->attributes->get('tenant'))->id ?? auth()->user()?->tenant_id;
        if ($conversionApi->tenant_id !== $tenantId) {
            abort(403);
        }

        $conversionApi->is_active = !$conversionApi->is_active;
        $conversionApi->save();

        $statusText = $conversionApi->is_active ? 'تفعيل' : 'تعطيل';
        return redirect()->back()->with('success', "تم {$statusText} البيكسل بنجاح ✓");
    }

    public function testEvent(Request $request, ConversionApiPixel $conversionApi)
    {
        $tenantId = optional($request->attributes->get('tenant'))->id ?? auth()->user()?->tenant_id;
        if ($conversionApi->tenant_id !== $tenantId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $result = ConversionApiService::sendTestEvent($conversionApi);

        return response()->json($result);
    }
}
