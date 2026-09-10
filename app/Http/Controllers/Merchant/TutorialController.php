<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Inertia\Inertia;

class TutorialController extends Controller
{
    public function index()
    {
        $tutorials = Tutorial::where('is_published', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function($t) {
                return [
                    'id' => $t->id,
                    'title' => $t->title,
                    'category' => $t->category,
                    'youtube_url' => $t->youtube_url,
                    'youtube_id' => $t->youtube_id,
                    'embed_url' => $t->embed_url,
                    'description' => $t->description,
                    'duration' => $t->duration ?: 'ريلز تعليمي',
                    'is_reel' => str_contains($t->youtube_url, '/shorts/'),
                    'sort_order' => $t->sort_order,
                ];
            });

        $preferredOrder = ['الكل', 'البداية والسريعة', 'إضافة منتجات', 'المنتجات والأقسام', 'إعدادات المتجر والتصميم', 'الطلبات والمبيعات', 'عام'];
        $existingCategories = $tutorials->pluck('category')->unique()->values()->toArray();
        $sortedCategories = array_values(array_unique(array_merge($preferredOrder, $existingCategories)));
        // Keep only categories that exist in tutorials or 'الكل'
        $categories = array_values(array_filter($sortedCategories, function($cat) use ($existingCategories) {
            return $cat === 'الكل' || in_array($cat, $existingCategories);
        }));

        return Inertia::render('Merchant/Tutorials/Index', [
            'tutorials' => $tutorials,
            'categories' => $categories,
        ]);
    }
}
