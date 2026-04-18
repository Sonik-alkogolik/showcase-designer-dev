<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            // Защитная очистка, чтобы удаление из MoonShine гарантированно
            // убирало все связанные доменные данные пользователя.
            $user->importRuns()->delete();
            $user->subscriptions()->delete();
            $user->tokens()->delete();

            $user->shops()->get()->each(function (Shop $shop) {
                $shop->delete();
            });
        });
    }

    /**
     * Атрибуты, доступные для массового присвоения.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
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
            'must_change_password' => 'boolean',
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
        return (int) ($this->getPlanFeatures()['shops_limit'] ?? 0);
    }

    /**
     * Получить лимит товаров по тарифу
     */
    public function getProductsLimit(): int
    {
        return (int) ($this->getPlanFeatures()['products_limit'] ?? 0);
    }

    /**
     * Текущий активный тариф пользователя.
     */
    public function getActivePlan(): ?string
    {
        return $this->activeSubscription()->first()?->plan;
    }

    /**
     * Единая таблица capabilities для тарифов.
     */
    public function getPlanFeatures(): array
    {
        $plan = $this->getActivePlan();

        if (!$plan) {
            return [
                'shops_limit' => 0,
                'products_limit' => 0,
                'can_import_excel' => false,
            ];
        }

        return match($plan) {
            'starter' => [
                'shops_limit' => 1,
                'products_limit' => 20,
                'can_import_excel' => false,
            ],
            'business' => [
                'shops_limit' => 5,
                'products_limit' => 200,
                'can_import_excel' => true,
            ],
            'premium' => [
                'shops_limit' => 10,
                'products_limit' => 10000,
                'can_import_excel' => true,
            ],
            default => [
                'shops_limit' => 0,
                'products_limit' => 0,
                'can_import_excel' => false,
            ],
        };
    }

    /**
     * Доступен ли Excel-импорт для активного тарифа.
     */
    public function canImportExcel(): bool
    {
        return (bool) ($this->getPlanFeatures()['can_import_excel'] ?? false);
    }



        /**
     * Получить магазины пользователя
     */
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    /**
     * Запуски импорта пользователя
     */
    public function importRuns()
    {
        return $this->hasMany(ImportRun::class);
    }

    public function telegramPasswordResetTokens()
    {
        return $this->hasMany(TelegramPasswordResetToken::class);
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
