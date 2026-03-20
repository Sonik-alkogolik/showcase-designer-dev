<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Для каждого существующего магазина создаём категорию "Без категории"
        $shops = DB::table('shops')->get();
        
        foreach ($shops as $shop) {
            // Проверяем, есть ли уже категория с таким названием
            $existing = DB::table('categories')
                ->where('shop_id', $shop->id)
                ->where('name', 'Без категории')
                ->first();
            
            if (!$existing) {
                DB::table('categories')->insert([
                    'shop_id' => $shop->id,
                    'name' => 'Без категории',
                    'slug' => 'bez-kategorii',
                    'description' => 'Товары без категории',
                    'sort_order' => 999999,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем созданные категории
        DB::table('categories')
            ->where('name', 'Без категории')
            ->delete();
    }
};