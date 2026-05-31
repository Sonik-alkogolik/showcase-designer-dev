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
        $this->notifyEmail($ticket);
        $this->notifyTelegram($ticket);
    }

    private function notifyEmail(SupportTicket $ticket): void
    {
        $email = trim((string) config('services.support.admin_email', ''));
        if ($email === '') {
            return;
        }

        try {
            Mail::raw($this->buildText($ticket), function ($message) use ($email, $ticket) {
                $message->to($email)
                    ->subject('Новый тикет e-TGO #' . $ticket->id . ': ' . $ticket->subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Support ticket email notification failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyTelegram(SupportTicket $ticket): void
    {
        $chatId = trim((string) config('services.support.telegram_chat_id', ''));
        if ($chatId === '') {
            return;
        }

        SendTelegramMessageJob::dispatch((int) $chatId, $this->buildText($ticket))->afterResponse();
    }

    private function buildText(SupportTicket $ticket): string
    {
        $category = SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category;

        return implode("\n", [
            "Новый тикет e-TGO #{$ticket->id}",
            "Тема: {$ticket->subject}",
            "Категория: {$category}",
            'Пользователь: ' . ($ticket->user_id ? "#{$ticket->user_id}" : 'guest'),
            'Email: ' . ($ticket->user_email ?: 'не указан'),
            'URL: ' . ($ticket->current_url ?: 'не передан'),
            'Браузер: ' . ($ticket->browser ?: 'не передан'),
            '',
            mb_substr($ticket->message, 0, 900),
        ]);
    }
}
