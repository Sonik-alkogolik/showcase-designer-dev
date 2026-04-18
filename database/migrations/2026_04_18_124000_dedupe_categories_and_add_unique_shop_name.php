<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $database = DB::getDatabaseName();

            return DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function up(): void
    {
        // Шаг 1: безопасная дедупликация категорий в рамках одного shop_id + name
        $duplicates = DB::table('categories')
            ->select('shop_id', 'name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('shop_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $idsToMerge = DB::table('categories')
                ->where('shop_id', $dup->shop_id)
                ->where('name', $dup->name)
                ->where('id', '!=', $dup->keep_id)
                ->pluck('id')
                ->all();

            if (empty($idsToMerge)) {
                continue;
            }

            DB::table('products')
                ->whereIn('category_id', $idsToMerge)
                ->update(['category_id' => $dup->keep_id]);

            DB::table('categories')
                ->whereIn('id', $idsToMerge)
                ->delete();
        }

        // Шаг 2: добавляем строгий UNIQUE по shop_id + name
        Schema::table('categories', function (Blueprint $table) {
            if (! $this->hasIndex('categories', 'categories_shop_name_unique')) {
                $table->unique(['shop_id', 'name'], 'categories_shop_name_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if ($this->hasIndex('categories', 'categories_shop_name_unique')) {
                $table->dropUnique('categories_shop_name_unique');
            }
        });
    }
};
