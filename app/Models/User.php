<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Атрибуты, доступные для массового присвоения.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
        'telegram_username',
        'telegram_avatar_url',
        'telegram_linked_at',
    ];

    /**
     * Атрибуты, скрытые при сериализации.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Кастинг атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_linked_at' => 'datetime',
        ];
    }

    /**
     * Проверяет, привязан ли пользователь к Telegram.
     *
     * @return bool
     */
    public function isTelegramLinked(): bool
    {
        return !is_null($this->telegram_id) && !is_null($this->telegram_linked_at);
    }

    /**
     * Привязывает пользовательский аккаунт к Telegram.
     *
     * @param int $telegramId
     * @param string|null $username
     * @return void
     */
    public function linkTelegram(int $telegramId, ?string $username = null, ?string $avatarUrl = null): void
    {
        $this->telegram_id = $telegramId;
        $this->telegram_username = $username ?: 'user_' . $telegramId;
        $this->telegram_avatar_url = $avatarUrl;
        $this->telegram_linked_at = now();
        $this->save();
    }

    /**
     * Отвязывает пользователя от Telegram.
     *
     * @return void
     */
    public function unlinkTelegram(): void
    {
        $this->telegram_id = null;
        $this->telegram_username = null;
        $this->telegram_avatar_url = null;
        $this->telegram_linked_at = null;
        $this->save();
    }
        /**
     * Получить все подписки пользователя
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Получить активную подписку пользователя
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest();
    }

    /**
     * Проверить, есть ли активная подписка
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Получить лимит магазинов по тарифу
     */
    public function getShopsLimit(): int
    {
        $subscription = $this->activeSubscription()->first();
        
        if (!$subscription) {
            return 0; // Без подписки нельзя создавать магазины
        }
        
        return match($subscription->plan) {
            'starter' => 1,
            'business' => 5,
            'premium' => 10,
            default => 0
        };
    }

    /**
     * Получить лимит товаров по тарифу
     */
    public function getProductsLimit(): int
    {
        $subscription = $this->activeSubscription()->first();
        
        if (!$subscription) {
            return 0;
        }
        
        return match($subscription->plan) {
            'starter' => 100,
            'business' => 1000,
            'premium' => 10000,
            default => 0
        };
    }



        /**
     * Получить магазины пользователя
     */
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    /**
     * Проверить, может ли пользователь создать новый магазин
     */
    public function canCreateMoreShops(): bool
    {
        $shopsCount = $this->shops()->count();
        $limit = $this->getShopsLimit();
        
        return $shopsCount < $limit;
    }

    /**
     * Обратная совместимость со старым именем метода.
     */
    public function canCreateShop(): bool
    {
        return $this->canCreateMoreShops();
    }

    /**
     * Получить оставшееся количество магазинов
     */
    public function getRemainingShops(): int
    {
        $shopsCount = $this->shops()->count();
        $limit = $this->getShopsLimit();
        
        return max(0, $limit - $shopsCount);
    }
}
