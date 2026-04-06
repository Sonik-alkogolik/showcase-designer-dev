<?php

namespace App\Console\Commands\Telegram;

use App\Support\TelegramHttp;
use Illuminate\Console\Command;

class DeleteWebhook extends Command
{
    protected $signature = 'telegram:delete-webhook {--drop-pending : Drop all pending updates on deleteWebhook}';
    protected $description = 'Удалить webhook Telegram бота';

    public function handle(): int
    {
        $token = trim((string) config('telegram.bots.mybot.token'));
        if ($token === '') {
            $this->error('❌ TELEGRAM_BOT_TOKEN не задан в .env');
            return self::FAILURE;
        }

        $dropPending = (bool) $this->option('drop-pending');

        try {
            $response = TelegramHttp::client()
                ->timeout(20)
                ->post(TelegramHttp::botMethodUrl($token, 'deleteWebhook'), [
                    'drop_pending_updates' => $dropPending,
                ]);

            if (! $response->ok()) {
                $this->error('❌ Ошибка deleteWebhook: ' . $response->body());
                return self::FAILURE;
            }

            $this->info('✅ Webhook удален.');
            $this->line('drop_pending_updates: ' . ($dropPending ? 'true' : 'false'));
            $this->call('telegram:webhook-info');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Ошибка при удалении webhook: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
