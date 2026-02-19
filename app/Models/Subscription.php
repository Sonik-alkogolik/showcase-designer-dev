<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'status',
        'expires_at',
        'auto_renew',
        'price',
        'payment_method',
        'yookassa_payment_id'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
        'price' => 'decimal:2'
    ];

    /**
     * Получить пользователя этой подписки
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверить, активна ли подписка
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }

    /**
     * Проверить, истекла ли подписка
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast());
    }
}