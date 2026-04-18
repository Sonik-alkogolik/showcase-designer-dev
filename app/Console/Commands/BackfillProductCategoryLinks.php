<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillProductCategoryLinks extends Command
{
    protected $signature = 'app:backfill-product-category-links
        {--shop_id= : Ограничить backfill конкретным магазином}
        {--dry-run : Только показать изменения без записи}';

    protected $description = 'Заполняет products.category_id по legacy-полю products.category (в пределах магазина)';

    public function handle(): int
    {
        $shopId = $this->option('shop_id');
        $dryRun = (bool) $this->option('dry-run');

        $query = Product::query()
            ->select(['id', 'shop_id', 'category', 'category_id'])
            ->whereNull('category_id')
            ->whereNotNull('category')
            ->whereRaw("TRIM(category) <> ''")
            ->when($shopId, fn ($q) => $q->where('shop_id', (int) $shopId))
            ->orderBy('id');

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Нет товаров для backfill (category_id уже заполнен или нет legacy category).');
            return self::SUCCESS;
        }

        $this->info("Найдено товаров для backfill: {$total}");
        if ($dryRun) {
            $this->warn('Режим dry-run: изменения в БД не будут сохранены.');
        }

        $updated = 0;
        $createdCategories = 0;

        $query->chunkById(200, function ($products) use (&$updated, &$createdCategories, $dryRun) {
            foreach ($products as $product) {
                $categoryName = trim((string) $product->category);
                if ($categoryName === '') {
                    continue;
                }

                $category = Category::query()
                    ->where('shop_id', $product->shop_id)
                    ->where('name', $categoryName)
                    ->first();

                if (! $category) {
                    $slugBase = Str::slug($categoryName) ?: 'category';
                    $slug = $slugBase;
                    $i = 2;

                    while (Category::query()
                        ->where('shop_id', $product->shop_id)
                        ->where('slug', $slug)
                        ->exists()) {
                        $slug = "{$slugBase}-{$i}";
                        $i++;
                    }

                    if (! $dryRun) {
                        $category = Category::create([
                            'shop_id' => $product->shop_id,
                            'name' => $categoryName,
                            'slug' => $slug,
                            'description' => null,
                            'sort_order' => 0,
                            'is_active' => true,
                        ]);
                    }

                    $createdCategories++;
                }

                if (! $dryRun) {
                    $product->category_id = $category->id;
                    $product->save();
                }

                $updated++;
            }
        });

        $this->newLine();
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Обработано товаров', $total],
                ['Обновлено category_id', $updated],
                ['Создано категорий', $createdCategories],
                ['Режим dry-run', $dryRun ? 'yes' : 'no'],
            ]
        );

        return self::SUCCESS;
    }
}
