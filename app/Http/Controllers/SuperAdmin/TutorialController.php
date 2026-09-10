<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TutorialController extends Controller
{
    public function index()
    {
        $tutorials = Tutorial::orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get()->map(function($t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'category' => $t->category,
                'youtube_url' => $t->youtube_url,
                'youtube_id' => $t->youtube_id,
                'embed_url' => $t->embed_url,
                'description' => $t->description,
                'duration' => $t->duration,
                'is_published' => (bool) $t->is_published,
                'sort_order' => $t->sort_order,
                'created_at' => $t->created_at ? $t->created_at->format('Y-m-d') : null,
            ];
        });

        $categories = ['الكل', 'البداية والسريعة', 'إضافة منتجات', 'المنتجات والأقسام', 'إعدادات المتجر والتصميم', 'الطلبات والمبيعات', 'صفحات الهبوط', 'عام'];

        return Inertia::render('SuperAdmin/Tutorials/Index', [
            'tutorials' => $tutorials,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'youtube_url' => 'required|string|max:500',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ]);

        Tutorial::create($validated);

        return redirect()->back()->with('success', 'تم إضافة الشرح التعليمي بنجاح ✓');
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'youtube_url' => 'required|string|max:500',
            'description' => 'nullable|string',
            'duration' => 'nullable|string|max:50',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ]);

        $tutorial->update($validated);

        return redirect()->back()->with('success', 'تم تحديث الشرح التعليمي بنجاح ✓');
    }

    public function toggle(Tutorial $tutorial)
    {
        $tutorial->update(['is_published' => !$tutorial->is_published]);
        return redirect()->back()->with('success', 'تم تغيير حالة نشر الدرس بنجاح ✓');
    }

    public function destroy(Tutorial $tutorial)
    {
        $tutorial->delete();
        return redirect()->back()->with('success', 'تم حذف الدرس بنجاح ✓');
    }
}
