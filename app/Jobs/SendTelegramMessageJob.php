<?php

namespace App\Jobs;

use App\Support\TelegramHttp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public function __construct(
        public readonly int $chatId,
        public readonly string $text
    ) {
    }

    public function handle(): void
    {
        $token = trim((string) config('telegram.bots.mybot.token'));
        if ($token === '') {
            Log::warning('Telegram bot token is not configured, skip sendMessage');
            return;
        }

        try {
            TelegramHttp::client()
                ->connectTimeout(1)
                ->timeout(2)
                ->post(TelegramHttp::botMethodUrl($token, 'sendMessage'), [
                    'chat_id' => $this->chatId,
                    'text' => $this->text,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage failed', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
