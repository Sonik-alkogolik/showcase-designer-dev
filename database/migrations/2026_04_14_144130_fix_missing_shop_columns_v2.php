<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'notification_username')) {
                $table->string('notification_username')->nullable();
            }
            if (!Schema::hasColumn('shops', 'webhook_url')) {
                $table->string('webhook_url')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'notification_username')) {
                $table->dropColumn('notification_username');
            }
            if (Schema::hasColumn('shops', 'webhook_url')) {
                $table->dropColumn('webhook_url');
            }
        });
    }
};
