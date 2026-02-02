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
    public function linkTelegram(int $telegramId, ?string $username = null): void
    {
        $this->telegram_id = $telegramId;
        $this->telegram_username = $username ?: 'user_' . $telegramId;
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
        $this->telegram_linked_at = null;
        $this->save();
    }
}