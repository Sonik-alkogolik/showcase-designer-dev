<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'bot_problem' => 'Проблема с ботом',
        'products_problem' => 'Проблема с товарами',
        'bug' => 'Ошибка',
        'question' => 'Вопрос',
    ];

    public const STATUSES = [
        'open' => 'Открыт',
        'in_progress' => 'В работе',
        'closed' => 'Закрыт',
    ];

    public const PRESETS = [
        'Не могу авторизоваться',
        'Не получается прикрепить бота',
        'Не получается прикрепить токен бота',
        'Не получается создать магазин',
    ];

    protected $fillable = [
        'user_id',
        'user_email',
        'category',
        'preset',
        'subject',
        'message',
        'status',
        'current_url',
        'browser',
        'reported_at',
        'screenshot_path',
        'admin_response',
        'last_admin_replied_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'last_admin_replied_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (SupportTicket $ticket): void {
            if (! $ticket->wasChanged('admin_response')) {
                return;
            }

            $response = trim((string) $ticket->admin_response);
            if ($response === '') {
                return;
            }

            $lastAdminMessage = $ticket->messages()
                ->where('sender_type', 'admin')
                ->latest('id')
                ->first();

            if ($lastAdminMessage?->body === $response) {
                return;
            }

            $ticket->messages()->create([
                'sender_type' => 'admin',
                'sender_name' => 'Администратор',
                'body' => $response,
            ]);

            $ticket->forceFill([
                'last_admin_replied_at' => now(),
                'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status,
            ])->saveQuietly();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class);
    }
}
