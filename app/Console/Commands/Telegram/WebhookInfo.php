<?php

namespace App\Console\Commands\Telegram;

use App\Support\TelegramHttp;
use Illuminate\Console\Command;

class WebhookInfo extends Command
{
    protected $signature = 'telegram:webhook-info';
    protected $description = 'Показать текущее состояние webhook Telegram бота';

    public function handle(): int
    {
        $token = trim((string) config('telegram.bots.mybot.token'));
        if ($token === '') {
            $this->error('❌ TELEGRAM_BOT_TOKEN не задан в .env');
            return self::FAILURE;
        }

        try {
            $response = TelegramHttp::client()
                ->timeout(20)
                ->get(TelegramHttp::botMethodUrl($token, 'getWebhookInfo'));

            if (! $response->ok()) {
                $this->error('❌ Ошибка getWebhookInfo: ' . $response->body());
                return self::FAILURE;
            }

            $result = (array) $response->json('result', []);
            $this->line('Webhook URL: ' . (string) ($result['url'] ?? ''));
            $this->line('pending_update_count: ' . (string) ($result['pending_update_count'] ?? 0));
            $this->line('last_error_date: ' . (string) ($result['last_error_date'] ?? 0));
            $this->line('last_error_message: ' . (string) ($result['last_error_message'] ?? ''));
            $this->line('max_connections: ' . (string) ($result['max_connections'] ?? ''));
            $this->line('ip_address: ' . (string) ($result['ip_address'] ?? ''));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка при запросе webhook info: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
