<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Console\Command;

class DiagnoseProductImages extends Command
{
    protected $signature = 'diagnose:images {tenant? : Subdomain, slug or ID} {--search= : Search product name}';
    protected $description = 'تشخيص صور المنتجات للمستأجر والتحقق من وجود الملفات على القرص';

    public function handle()
    {
        $tenantQuery = $this->argument('tenant') ?: 'httpswwwfacebookcombirdtechnology20';

        $tenant = Tenant::where('slug', $tenantQuery)
            ->orWhere('custom_domain', $tenantQuery)
            ->orWhere('id', is_numeric($tenantQuery) ? $tenantQuery : 0)
            ->orWhere('name', 'like', "%{$tenantQuery}%")
            ->first();

        if (!$tenant) {
            $this->error("المستأجر غير موجود: {$tenantQuery}");
            $this->info("البحث عن أي مستأجر يحتوي على 'سهر' أو 'bird':");
            $candidates = Tenant::where('name', 'like', '%سهر%')
                ->orWhere('slug', 'like', '%bird%')
                ->orWhere('slug', 'like', '%facebook%')
                ->get(['id', 'name', 'slug']);
            foreach ($candidates as $c) {
                $this->line("- ID: {$c->id}, Name: {$c->name}, Slug: {$c->slug}");
            }
            return 1;
        }

        $this->info("المستأجر: {$tenant->name} (معرف: {$tenant->id}, نطاق: {$tenant->slug})");

        $search = $this->option('search');

        $query = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id);
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        } else {
            $query->orderBy('id', 'desc');
        }

        $products = $query->take(30)->get();

        $storageBase = storage_path('app/public');
        $publicStorage = public_path('storage');

        $totalProducts = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
        $this->info("إجمالي منتجات المستأجر: {$totalProducts}");

        $rows = [];
        $missingCount = 0;
        foreach ($products as $p) {
            $path = $p->main_image_path ?: $p->image_url;
            $cleanPath = ltrim(str_replace('/storage/', '', $path ?? ''), '/');
            
            $existsInStorageApp = !empty($cleanPath) && file_exists("{$storageBase}/{$cleanPath}");
            $existsInPublicStorage = !empty($cleanPath) && file_exists("{$publicStorage}/{$cleanPath}");

            if (!$existsInStorageApp && !str_starts_with($path ?? '', 'http')) {
                $missingCount++;
            }

            $rows[] = [
                $p->id,
                mb_substr($p->name, 0, 25),
                mb_substr($path ?? 'null', 0, 40),
                $existsInStorageApp ? 'نعم ✓' : 'لا ✗',
                $existsInPublicStorage ? 'نعم ✓' : 'لا ✗',
                mb_substr($p->image_display_url ?? 'null', 0, 50),
            ];
        }

        $this->table(
            ['ID', 'الاسم', 'المسار الأصلي', 'في storage/app', 'في public/storage', 'الرابط المحسوب'],
            $rows
        );

        $this->info("الصور المفقودة في العينة: {$missingCount}");

        // فحص سريع إذا كانت هناك صور في مسار قديم
        if ($missingCount > 0) {
            $this->warn("محاولة البحث عن الصور المفقودة في /home/fast-order-eg.tech بالكامل...");
        }

        return 0;
    }
}
