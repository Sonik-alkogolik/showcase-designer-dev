<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'shop_id',
        'customer_name',
        'phone',
        'total',
        'delivery_name',
        'delivery_price',
        'status',
        'yookassa_payment_id',
        'items',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'delivery_price' => 'decimal:2',
        'items' => 'array',
    ];

    /**
     * Связь с магазином
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Проверка, оплачен ли заказ
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Отметить заказ как оплаченный
     */
    public function markAsPaid(string $paymentId): void
    {
        $this->status = 'paid';
        $this->yookassa_payment_id = $paymentId;
        $this->save();
    }

    /**
     * Отменить заказ
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Получить общую сумму с доставкой
     */
    public function getTotalWithDeliveryAttribute(): float
    {
        return $this->total + $this->delivery_price;
    }
}