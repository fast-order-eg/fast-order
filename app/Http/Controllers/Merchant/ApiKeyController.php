<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiKeyController extends Controller
{
    /**
     * عرض قائمة مفاتيح API للمتجر الحالي
     */
    public function index()
    {
        $tenant = app(\App\Models\Tenant::class);

        $apiKeys = ApiKey::where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'key' => $key->key,
                'key_preview' => substr($key->key, 0, 12) . '...' . substr($key->key, -4),
                'is_active' => $key->isActive(),
                'last_used_at' => $key->last_used_at?->diffForHumans(),
                'created_at' => $key->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Merchant/ApiKeys/Index', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * إنشاء مفتاح API جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tenant = app(\App\Models\Tenant::class);

        $apiKey = ApiKey::generate($tenant->id, $request->name);

        return back()->with([
            'success' => 'تم إنشاء مفتاح API بنجاح.',
            'new_key' => $apiKey->key, // نعرضه مرة واحدة فقط
        ]);
    }

    /**
     * إلغاء مفتاح API
     */
    public function destroy(ApiKey $apiKey)
    {
        // التأكد إن المفتاح يخص المتجر الحالي
        $tenant = app(\App\Models\Tenant::class);
        abort_unless($apiKey->tenant_id === $tenant->id, 403);

        $apiKey->delete();

        return back()->with('success', 'تم حذف مفتاح API بنجاح.');
    }
}
