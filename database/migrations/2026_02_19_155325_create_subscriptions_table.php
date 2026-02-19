<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('plan', ['starter', 'business', 'premium'])->default('starter');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('expired');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->string('yookassa_payment_id')->nullable();
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};