<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDataArchitecture extends Command
{
    protected $signature = 'app:audit-data-architecture {--shop_id= : Ограничить аудит конкретным магазином}';

    protected $description = 'Проверка целостности данных и tenant-изоляции (shops/categories/products/orders/subscriptions)';

    public function handle(): int
    {
        $shopId = $this->option('shop_id');

        $this->info('=== Аудит архитектуры данных ===');
        if ($shopId) {
            $this->line("Фильтр по shop_id: {$shopId}");
        }

        $issues = 0;

        $issues += $this->auditOrphans($shopId);
        $issues += $this->auditCategoryConsistency($shopId);
        $issues += $this->auditLegacyCategoryUsage($shopId);

        $this->newLine();
        if ($issues === 0) {
            $this->info('OK: критичных проблем не найдено.');
            return self::SUCCESS;
        }

        $this->warn("Найдено проблем: {$issues}");
        $this->line('Рекомендуется запустить backfill и cleanup перед добавлением строгих UNIQUE-ограничений.');

        return self::FAILURE;
    }

    private function auditOrphans(?string $shopId): int
    {
        $this->newLine();
        $this->info('[1/3] Осиротевшие связи');

        $issues = 0;

        $productsOrphanShop = DB::table('products as p')
            ->leftJoin('shops as s', 's.id', '=', 'p.shop_id')
            ->when($shopId, fn ($q) => $q->where('p.shop_id', (int) $shopId))
            ->whereNull('s.id')
            ->count();

        $ordersOrphanShop = DB::table('orders as o')
            ->leftJoin('shops as s', 's.id', '=', 'o.shop_id')
            ->when($shopId, fn ($q) => $q->where('o.shop_id', (int) $shopId))
            ->whereNull('s.id')
            ->count();

        $categoriesOrphanShop = DB::table('categories as c')
            ->leftJoin('shops as s', 's.id', '=', 'c.shop_id')
            ->when($shopId, fn ($q) => $q->where('c.shop_id', (int) $shopId))
            ->whereNull('s.id')
            ->count();

        $subscriptionsOrphanUser = DB::table('subscriptions as sub')
            ->leftJoin('users as u', 'u.id', '=', 'sub.user_id')
            ->whereNull('u.id')
            ->count();

        $rows = [
            ['products -> shops', $productsOrphanShop],
            ['orders -> shops', $ordersOrphanShop],
            ['categories -> shops', $categoriesOrphanShop],
            ['subscriptions -> users', $subscriptionsOrphanUser],
        ];

        $this->table(['Связь', 'Проблемных строк'], $rows);

        foreach ($rows as $row) {
            if ((int) $row[1] > 0) {
                $issues++;
            }
        }

        return $issues;
    }

    private function auditCategoryConsistency(?string $shopId): int
    {
        $this->newLine();
        $this->info('[2/3] Консистентность категорий');

        $issues = 0;

        $productsCategoryMismatch = DB::table('products as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->when($shopId, fn ($q) => $q->where('p.shop_id', (int) $shopId))
            ->whereColumn('p.shop_id', '!=', 'c.shop_id')
            ->count();

        $duplicateCategories = DB::table('categories')
            ->select('shop_id', 'name', DB::raw('COUNT(*) as cnt'))
            ->when($shopId, fn ($q) => $q->where('shop_id', (int) $shopId))
            ->groupBy('shop_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $rows = [
            ['products.category_id указывает на category другого shop_id', $productsCategoryMismatch],
            ['Дубликаты categories (shop_id + name)', $duplicateCategories],
        ];

        $this->table(['Проверка', 'Проблемных строк/групп'], $rows);

        foreach ($rows as $row) {
            if ((int) $row[1] > 0) {
                $issues++;
            }
        }

        return $issues;
    }

    private function auditLegacyCategoryUsage(?string $shopId): int
    {
        $this->newLine();
        $this->info('[3/3] Legacy-поле products.category');

        $issues = 0;

        $productsWithoutCategoryIdButText = DB::table('products')
            ->when($shopId, fn ($q) => $q->where('shop_id', (int) $shopId))
            ->whereNull('category_id')
            ->whereNotNull('category')
            ->whereRaw("TRIM(category) <> ''")
            ->count();

        $productsWithCategoryNameMismatch = DB::table('products as p')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->when($shopId, fn ($q) => $q->where('p.shop_id', (int) $shopId))
            ->whereNotNull('p.category')
            ->whereRaw("TRIM(p.category) <> ''")
            ->whereRaw('p.category <> c.name')
            ->count();

        $rows = [
            ['Товары с category-текстом, но без category_id', $productsWithoutCategoryIdButText],
            ['Товары, где products.category != categories.name', $productsWithCategoryNameMismatch],
        ];

        $this->table(['Проверка', 'Количество'], $rows);

        foreach ($rows as $row) {
            if ((int) $row[1] > 0) {
                $issues++;
            }
        }

        return $issues;
    }
}
