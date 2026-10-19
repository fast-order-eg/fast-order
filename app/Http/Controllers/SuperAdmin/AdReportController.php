<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdCampaignReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdReportController extends Controller
{
    /**
     * Display a listing of shared ad campaign reports.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = AdCampaignReport::with('creator')->latest();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
        }

        $reports = $query->paginate(20)->withQueryString();

        // Transform reports to include public URL
        $reports->getCollection()->transform(function ($report) {
            return [
                'id'           => $report->id,
                'name'         => $report->name,
                'campaign_ids' => $report->getCleanCampaignIds(),
                'campaign_ids_raw' => implode("\n", $report->getCleanCampaignIds()),
                'campaigns_count' => count($report->getCleanCampaignIds()),
                'token'        => $report->token,
                'public_url'   => $report->getPublicUrl(),
                'is_active'    => (bool) $report->is_active,
                'notes'        => $report->notes,
                'show_summary' => (bool) $report->show_summary,
                'creator_name' => $report->creator?->name ?? 'Super Admin',
                'created_at'   => $report->created_at?->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('SuperAdmin/AdReports/Index', [
            'reports' => $reports,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created shared ad campaign report.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'campaign_ids' => ['required', 'string'],
            'notes'        => ['nullable', 'string', 'max:5000'],
            'show_summary' => ['nullable', 'boolean'],
        ], [
            'name.required'         => 'يرجى إدخال اسم العميل أو اسم التقرير.',
            'campaign_ids.required' => 'يرجى إدخال معرف حملة إعلانية واحدة على الأقل.',
        ]);

        $campaignIds = $this->parseCampaignIds($request->input('campaign_ids'));

        if (empty($campaignIds)) {
            return back()->withErrors(['campaign_ids' => 'يرجى إدخال أرقام حملات إعلانية صالحة.']);
        }

        $token = Str::random(32);

        $report = AdCampaignReport::create([
            'name'         => trim($request->input('name')),
            'campaign_ids' => $campaignIds,
            'token'        => $token,
            'is_active'    => true,
            'notes'        => $request->input('notes'),
            'show_summary' => $request->boolean('show_summary'),
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('superadmin.ad-reports.index')
            ->with('success', "تم إنشاء رابط تقرير الإعلانات بنجاح للعميل ({$report->name})");
    }

    /**
     * Update an existing shared ad campaign report.
     */
    public function update(Request $request, AdCampaignReport $adReport): RedirectResponse
    {
        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'campaign_ids' => ['required', 'string'],
            'notes'        => ['nullable', 'string', 'max:5000'],
            'show_summary' => ['nullable', 'boolean'],
        ], [
            'name.required'         => 'يرجى إدخال اسم العميل أو اسم التقرير.',
            'campaign_ids.required' => 'يرجى إدخال معرف حملة إعلانية واحدة على الأقل.',
        ]);

        $campaignIds = $this->parseCampaignIds($request->input('campaign_ids'));

        if (empty($campaignIds)) {
            return back()->withErrors(['campaign_ids' => 'يرجى إدخال أرقام حملات إعلانية صالحة.']);
        }

        $adReport->update([
            'name'         => trim($request->input('name')),
            'campaign_ids' => $campaignIds,
            'notes'        => $request->input('notes'),
            'show_summary' => $request->boolean('show_summary'),
        ]);

        return redirect()->route('superadmin.ad-reports.index')
            ->with('success', "تم تحديث تقرير الإعلانات بنجاح للعميل ({$adReport->name})");
    }

    /**
     * Toggle active/disabled status for an ad report.
     */
    public function toggleStatus(AdCampaignReport $adReport): RedirectResponse
    {
        $adReport->is_active = !$adReport->is_active;
        $adReport->save();

        $msg = $adReport->is_active ? 'تم تفعيل رابط التقرير بنجاح' : 'تم تعطيل رابط التقرير بنجاح';

        return back()->with('success', $msg);
    }

    /**
     * Delete an ad report.
     */
    public function destroy(AdCampaignReport $adReport): RedirectResponse
    {
        $name = $adReport->name;
        $adReport->delete();

        return back()->with('success', "تم حذف تقرير الإعلانات للعميل ({$name}) بنجاح");
    }

    /**
     * Helper to parse string input into clean unique array of campaign IDs.
     */
    protected function parseCampaignIds(string $input): array
    {
        $tokens = preg_split('/[\r\n,;\s]+/', $input);
        $result = [];

        foreach ($tokens as $token) {
            $cleaned = trim($token);
            if (!empty($cleaned) && !in_array($cleaned, $result, true)) {
                $result[] = $cleaned;
            }
        }

        return $result;
    }
}
