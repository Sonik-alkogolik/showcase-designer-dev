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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('bot_token')->nullable(); // Будет шифроваться в модели
            $table->string('notification_chat_id')->nullable();
            $table->string('delivery_name')->default('Самовывоз');
            $table->decimal('delivery_price', 8, 2)->default(0);
            $table->timestamps();
            
            // Индексы
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};