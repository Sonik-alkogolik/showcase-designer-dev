<?php

namespace App\Services;

use App\Jobs\SendTelegramMessageJob;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportTicketNotificationService
{
    public function notifyCreated(SupportTicket $ticket): void
    {
        $this->notifyEmail($ticket, 'Новый тикет e-TGO');
        $this->notifyTelegram($ticket, $this->buildText($ticket, 'Новый тикет e-TGO'));
    }

    public function notifyUserReply(SupportTicket $ticket, string $reply): void
    {
        $this->notifyEmail($ticket, 'Новый ответ пользователя в тикете e-TGO', $reply);
        $this->notifyTelegram($ticket, $this->buildText($ticket, 'Новый ответ пользователя в тикете e-TGO', $reply));
    }

    private function notifyEmail(SupportTicket $ticket, string $title, ?string $reply = null): void
    {
        $email = trim((string) config('services.support.admin_email', ''));
        if ($email === '') {
            return;
        }

        try {
            Mail::raw($this->buildText($ticket, $title, $reply), function ($message) use ($email, $ticket, $title) {
                $message->to($email)
                    ->subject($title . ' #' . $ticket->id . ': ' . $ticket->subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Support ticket email notification failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyTelegram(SupportTicket $ticket, string $text): void
    {
        $chatId = trim((string) config('services.support.telegram_chat_id', ''));
        if ($chatId === '') {
            return;
        }

        SendTelegramMessageJob::dispatch((int) $chatId, $text)->afterResponse();
    }

    private function buildText(SupportTicket $ticket, string $title, ?string $reply = null): string
    {
        $category = SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category;
        $body = $reply !== null ? $reply : $ticket->message;

        return implode("\n", [
            "{$title} #{$ticket->id}",
            "Тема: {$ticket->subject}",
            "Категория: {$category}",
            'Пользователь: ' . ($ticket->user_id ? "#{$ticket->user_id}" : 'guest'),
            'Email: ' . ($ticket->user_email ?: 'не указан'),
            'URL: ' . ($ticket->current_url ?: 'не передан'),
            'Браузер: ' . ($ticket->browser ?: 'не передан'),
            '',
            mb_substr($body, 0, 900),
        ]);
    }
}
