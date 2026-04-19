<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Для других драйверов оставляем как есть.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1) Чистим магазины без владельца и все доменные данные, чтобы FK можно было безопасно восстановить.
        $orphanShopIds = DB::table('shops')
            ->leftJoin('users', 'users.id', '=', 'shops.user_id')
            ->whereNull('users.id')
            ->pluck('shops.id')
            ->all();

        if (! empty($orphanShopIds)) {
            if (Schema::hasTable('import_runs')) {
                DB::table('import_runs')->whereIn('shop_id', $orphanShopIds)->delete();
            }

            if (Schema::hasTable('orders')) {
                DB::table('orders')->whereIn('shop_id', $orphanShopIds)->delete();
            }

            $orphanProductIds = [];
            if (Schema::hasTable('products')) {
                $orphanProductIds = DB::table('products')
                    ->whereIn('shop_id', $orphanShopIds)
                    ->pluck('id')
                    ->all();
            }

            if (! empty($orphanProductIds) && Schema::hasTable('category_product')) {
                DB::table('category_product')->whereIn('product_id', $orphanProductIds)->delete();
            }

            if (Schema::hasTable('products')) {
                DB::table('products')->whereIn('shop_id', $orphanShopIds)->delete();
            }

            if (Schema::hasTable('categories')) {
                DB::table('categories')->whereIn('shop_id', $orphanShopIds)->delete();
            }

            DB::table('shops')->whereIn('id', $orphanShopIds)->delete();
        }

        // 2) Принудительно приводим FK shops.user_id -> users.id к ON DELETE CASCADE.
        $constraints = DB::select(
            "
            SELECT kcu.CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.TABLE_NAME = 'shops'
              AND kcu.COLUMN_NAME = 'user_id'
              AND kcu.REFERENCED_TABLE_NAME = 'users'
            "
        );

        foreach ($constraints as $constraint) {
            $name = $constraint->name;
            DB::statement("ALTER TABLE `shops` DROP FOREIGN KEY `{$name}`");
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->foreign('user_id', 'shops_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $constraints = DB::select(
            "
            SELECT kcu.CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.TABLE_NAME = 'shops'
              AND kcu.COLUMN_NAME = 'user_id'
              AND kcu.REFERENCED_TABLE_NAME = 'users'
            "
        );

        foreach ($constraints as $constraint) {
            $name = $constraint->name;
            DB::statement("ALTER TABLE `shops` DROP FOREIGN KEY `{$name}`");
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->foreign('user_id', 'shops_user_id_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};
