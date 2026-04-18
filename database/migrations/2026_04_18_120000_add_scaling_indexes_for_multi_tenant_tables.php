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
        Schema::table('shops', function (Blueprint $table) {
            if (! $this->hasIndex('shops', 'shops_user_created_idx')) {
                $table->index(['user_id', 'created_at'], 'shops_user_created_idx');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (! $this->hasIndex('categories', 'categories_shop_active_sort_idx')) {
                $table->index(['shop_id', 'is_active', 'sort_order'], 'categories_shop_active_sort_idx');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! $this->hasIndex('products', 'products_shop_category_idx')) {
                $table->index(['shop_id', 'category_id'], 'products_shop_category_idx');
            }

            if (! $this->hasIndex('products', 'products_shop_stock_created_idx')) {
                $table->index(['shop_id', 'in_stock', 'created_at'], 'products_shop_stock_created_idx');
            }

            if (! $this->hasIndex('products', 'products_shop_slider_idx')) {
                $table->index(['shop_id', 'show_in_slider'], 'products_shop_slider_idx');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! $this->hasIndex('orders', 'orders_shop_status_created_idx')) {
                $table->index(['shop_id', 'status', 'created_at'], 'orders_shop_status_created_idx');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! $this->hasIndex('subscriptions', 'subscriptions_user_status_expires_idx')) {
                $table->index(['user_id', 'status', 'expires_at'], 'subscriptions_user_status_expires_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if ($this->hasIndex('shops', 'shops_user_created_idx')) {
                $table->dropIndex('shops_user_created_idx');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if ($this->hasIndex('categories', 'categories_shop_active_sort_idx')) {
                $table->dropIndex('categories_shop_active_sort_idx');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if ($this->hasIndex('products', 'products_shop_category_idx')) {
                $table->dropIndex('products_shop_category_idx');
            }

            if ($this->hasIndex('products', 'products_shop_stock_created_idx')) {
                $table->dropIndex('products_shop_stock_created_idx');
            }

            if ($this->hasIndex('products', 'products_shop_slider_idx')) {
                $table->dropIndex('products_shop_slider_idx');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if ($this->hasIndex('orders', 'orders_shop_status_created_idx')) {
                $table->dropIndex('orders_shop_status_created_idx');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if ($this->hasIndex('subscriptions', 'subscriptions_user_status_expires_idx')) {
                $table->dropIndex('subscriptions_user_status_expires_idx');
            }
        });
    }
};
