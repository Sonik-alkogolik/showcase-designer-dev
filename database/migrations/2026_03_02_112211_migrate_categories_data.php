<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Переносим существующие категории из products в таблицу categories
        $products = DB::table('products')
            ->select('shop_id', 'category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->get();

        foreach ($products as $product) {
            // Проверяем, есть ли уже такая категория для этого магазина
            $existingCategory = DB::table('categories')
                ->where('shop_id', $product->shop_id)
                ->where('name', $product->category)
                ->first();

            if (!$existingCategory) {
                // Создаём категорию
                $categoryId = DB::table('categories')->insertGetId([
                    'shop_id' => $product->shop_id,
                    'name' => $product->category,
                    'slug' => \Str::slug($product->category),
                    'sort_order' => 0,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Обновляем товары с этой категорией
                DB::table('products')
                    ->where('shop_id', $product->shop_id)
                    ->where('category', $product->category)
                    ->update(['category_id' => $categoryId]);
            } else {
                // Категория уже есть, просто обновляем товары
                DB::table('products')
                    ->where('shop_id', $product->shop_id)
                    ->where('category', $product->category)
                    ->update(['category_id' => $existingCategory->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В обратную сторону не переносим, просто очищаем category_id
        DB::table('products')->update(['category_id' => null]);
    }
};