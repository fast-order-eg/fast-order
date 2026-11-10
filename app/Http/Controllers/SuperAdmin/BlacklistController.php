<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BlacklistRecord;
use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\RtoShieldService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class BlacklistController extends Controller
{
    /**
     * Display a listing of all blacklist records across all tenants.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $type = trim((string) $request->input('type', ''));
        $status = trim((string) $request->input('status', '')); // 'all', 'global', 'whitelisted', 'local'

        $query = BlacklistRecord::withoutGlobalScope(TenantScope::class)->with('tenant');

        if ($search !== '') {
            $query->where('value', 'like', "%{$search}%");
        }

        if ($type !== '' && in_array($type, ['phone', 'ip', 'email'])) {
            $query->where('type', $type);
        }

        if ($status === 'global') {
            $query->where('is_global', true);
        } elseif ($status === 'whitelisted') {
            $query->where('is_whitelisted', true);
        } elseif ($status === 'local') {
            $query->where('is_global', false)->where('is_whitelisted', false);
        }

        $records = $query->latest()
            ->paginate(20)
            ->withQueryString();

        // Attach quick RTO assessment for displayed records
        $records->getCollection()->transform(function ($record) {
            if ($record->type === 'phone') {
                $assessment = RtoShieldService::assessRisk($record->value);
                $record->rto_stats = [
                    'risk_level' => $assessment['risk_level'],
                    'badge_text' => $assessment['badge_text'],
                    'badge_color' => $assessment['badge_color'],
                    'total_orders' => $assessment['total_orders'],
                    'delivered_orders' => $assessment['delivered_orders'],
                    'returned_orders' => $assessment['returned_orders'],
                    'delivery_rate' => $assessment['delivery_rate'],
                    'reports_count' => $assessment['reports_count'],
                ];
            } else {
                $record->rto_stats = null;
            }
            return $record;
        });

        // Overview counters
        $stats = [
            'total' => BlacklistRecord::withoutGlobalScope(TenantScope::class)->count(),
            'global_blocked' => BlacklistRecord::withoutGlobalScope(TenantScope::class)->where('is_global', true)->count(),
            'whitelisted' => BlacklistRecord::withoutGlobalScope(TenantScope::class)->where('is_whitelisted', true)->count(),
            'phone_records' => BlacklistRecord::withoutGlobalScope(TenantScope::class)->where('type', 'phone')->count(),
        ];

        return Inertia::render('SuperAdmin/Blacklist/Index', [
            'records' => $records,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Store a new global blacklist or whitelist entry directly.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:phone,ip,email'],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'action_type' => ['required', 'string', 'in:global_ban,whitelist'],
        ]);

        $value = trim($validated['value']);
        $isGlobal = $validated['action_type'] === 'global_ban';
        $isWhitelisted = $validated['action_type'] === 'whitelist';

        // Check if record already exists
        $record = BlacklistRecord::withoutGlobalScope(TenantScope::class)
            ->where('type', $validated['type'])
            ->where('value', $value)
            ->first();

        if ($record) {
            $record->update([
                'reason' => $validated['reason'] ?: $record->reason,
                'is_global' => $isGlobal,
                'is_whitelisted' => $isWhitelisted,
            ]);
        } else {
            BlacklistRecord::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => null, // Platform level
                'type' => $validated['type'],
                'value' => $value,
                'reason' => $validated['reason'] ?: ($isWhitelisted ? 'فك حظر واعتماد موثوق من الإدارة' : 'حظر عام من الإدارة'),
                'is_global' => $isGlobal,
                'is_whitelisted' => $isWhitelisted,
            ]);
        }

        $msg = $isWhitelisted
            ? "تم اعتماد الرقم ({$value}) كموثوق وفك الحظر عنه لجميع التجار بنجاح ✓"
            : "تم إضافة ({$value}) في قائمة الحظر العام على مستوى المنصة بنجاح ✓";

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Toggle global ban status on a specific record.
     */
    public function toggleGlobal(int $id): RedirectResponse
    {
        $record = BlacklistRecord::withoutGlobalScope(TenantScope::class)->findOrFail($id);

        $newGlobal = !$record->is_global;
        $record->update([
            'is_global' => $newGlobal,
            // If turning into global ban, remove whitelist
            'is_whitelisted' => $newGlobal ? false : $record->is_whitelisted,
        ]);

        $statusText = $newGlobal ? 'حظر عام لكل المتاجر' : 'حظر محلي فقط للمتجر';
        return redirect()->back()->with('success', "تم تحديث حالة السجل إلى: {$statusText} ✓");
    }

    /**
     * Unblock / Whitelist a phone number globally for all merchants.
     */
    public function unblockGlobally(Request $request): RedirectResponse
    {
        $request->validate([
            'value' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:phone,ip,email'],
        ]);

        $value = trim($request->input('value'));
        $type = $request->input('type', 'phone');

        $variants = ($type === 'phone') ? RtoShieldService::getPhoneVariants($value) : [$value];

        // 1. Mark existing records as unblocked/whitelisted
        BlacklistRecord::withoutGlobalScope(TenantScope::class)
            ->where('type', $type)
            ->whereIn('value', $variants)
            ->update([
                'is_global' => false,
                'is_whitelisted' => true,
                'reason' => 'تم فك الحظر عنه من السوبر أدمن لجميع التجار',
            ]);

        // 2. Ensure at least one whitelist record exists
        $hasRecord = BlacklistRecord::withoutGlobalScope(TenantScope::class)
            ->where('type', $type)
            ->whereIn('value', $variants)
            ->exists();

        if (!$hasRecord) {
            BlacklistRecord::withoutGlobalScope(TenantScope::class)->create([
                'tenant_id' => null,
                'type' => $type,
                'value' => $value,
                'reason' => 'تم فك الحظر عنه من السوبر أدمن لجميع التجار',
                'is_global' => false,
                'is_whitelisted' => true,
            ]);
        }

        return redirect()->back()->with('success', "تم فك الحظر عن ({$value}) لجميع التجار بالكامل ولن يتم منعه من الطلب مجدداً ✓");
    }

    /**
     * Delete a blacklist record.
     */
    public function destroy(int $id): RedirectResponse
    {
        $record = BlacklistRecord::withoutGlobalScope(TenantScope::class)->findOrFail($id);
        $record->delete();

        return redirect()->back()->with('success', 'تم حذف السجل بنجاح.');
    }
}
