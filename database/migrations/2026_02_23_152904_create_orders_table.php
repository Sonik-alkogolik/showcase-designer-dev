<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->string('phone');
            $table->decimal('total', 10, 2);
            $table->string('delivery_name');
            $table->decimal('delivery_price', 8, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('yookassa_payment_id')->nullable();
            $table->json('items')->nullable(); // Сохраним состав заказа в JSON
            $table->timestamps();
            
            $table->index('shop_id');
            $table->index('status');
            $table->index('yookassa_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};