<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Console\Command;

class DiagnoseProductImages extends Command
{
    protected $signature = 'diagnose:images {tenant? : Subdomain, slug or ID}';
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

        $products = Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('id', 'desc')
            ->take(15)
            ->get();

        $this->info("فحص آخر 15 منتج:");
        $storageBase = storage_path('app/public');
        $publicStorage = public_path('storage');

        $rows = [];
        foreach ($products as $p) {
            $path = $p->main_image_path ?: $p->image_url;
            $cleanPath = ltrim(str_replace('/storage/', '', $path ?? ''), '/');
            
            $existsInStorageApp = file_exists("{$storageBase}/{$cleanPath}");
            $existsInPublicStorage = file_exists("{$publicStorage}/{$cleanPath}");

            $rows[] = [
                $p->id,
                mb_substr($p->name, 0, 20),
                $path ?? 'null',
                $existsInStorageApp ? 'نعم ✓' : 'لا ✗',
                $existsInPublicStorage ? 'نعم ✓' : 'لا ✗',
                $p->image_display_url ?? 'null',
            ];
        }

        $this->table(
            ['ID', 'الاسم', 'المسار الأصلي', 'في storage/app/public', 'في public/storage', 'الرابط المحسوب'],
            $rows
        );

        return 0;
    }
}
