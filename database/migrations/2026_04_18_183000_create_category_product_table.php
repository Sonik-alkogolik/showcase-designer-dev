<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'category_id'], 'category_product_unique');
            $table->index(['category_id', 'product_id'], 'category_product_category_idx');
        });

        // Backfill: переносим текущую primary category_id в pivot
        $rows = DB::table('products')
            ->select(['id as product_id', 'category_id'])
            ->whereNotNull('category_id')
            ->get();

        $now = now();
        $insert = [];

        foreach ($rows as $row) {
            $insert[] = [
                'product_id' => $row->product_id,
                'category_id' => $row->category_id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($insert)) {
            DB::table('category_product')->insertOrIgnore($insert);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
