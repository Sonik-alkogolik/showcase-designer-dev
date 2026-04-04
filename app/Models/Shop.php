<?php

namespace App\Models;

use App\Support\TelegramHttp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Shop extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'bot_token',
        'notification_chat_id',
        'notification_username',
        'delivery_name',
        'delivery_price',
        'webhook_url',
    ];

    protected $casts = [
        'delivery_price' => 'decimal:2',
    ];

    /**
     * Связь с пользователем (владелец магазина)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с товарами магазина
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Связь с заказами магазина
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    /**
     * Связь с категориями магазина
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
    /**
     * Шифрование токена бота при сохранении
     */
    public function setBotTokenAttribute($value)
    {
        $this->attributes['bot_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Дешифровка токена бота при получении
     */
    public function getBotTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Проверка валидности токена бота через Telegram API
     */
    public function validateBotToken(): bool
    {
        if (! $this->bot_token) {
            return false;
        }

        try {
            $response = TelegramHttp::client()
                ->timeout(10)
                ->get(TelegramHttp::botMethodUrl((string) $this->bot_token, 'getMe'));

            return $response->ok();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
