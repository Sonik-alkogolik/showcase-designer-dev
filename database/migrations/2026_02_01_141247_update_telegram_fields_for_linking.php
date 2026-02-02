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
        Schema::table('users', function (Blueprint $table) {
            // Переименовываем telegram_verified_at в telegram_linked_at
            $table->renameColumn('telegram_verified_at', 'telegram_linked_at');
            
            // Удаляем поле кода верификации
            $table->dropColumn('telegram_verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Восстанавливаем поле кода верификации
        $table->string('telegram_verification_code', 6)->nullable()->after('telegram_username');
        
        // Переименовываем обратно
        $table->renameColumn('telegram_linked_at', 'telegram_verified_at');
    });
}
};
