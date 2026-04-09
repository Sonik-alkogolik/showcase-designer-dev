<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'show_in_slider')) {
                $table->boolean('show_in_slider')->default(false)->after('in_stock');
                $table->index('show_in_slider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'show_in_slider')) {
                $table->dropIndex(['show_in_slider']);
                $table->dropColumn('show_in_slider');
            }
        });
    }
};

