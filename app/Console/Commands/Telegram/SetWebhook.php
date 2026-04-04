<?php

namespace App\Console\Commands\Telegram;

use App\Support\TelegramHttp;
use Illuminate\Console\Command;

class SetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url? : Full webhook URL, e.g. https://e-tgo.ru/api/telegram/webhook}';
    protected $description = 'Установить вебхук для бота Telegram';

    /**
     * Выполнение команды
     * 
     * Эта команда устанавливает вебхук для бота Telegram.
     * Вебхук — это механизм, при котором Telegram автоматически
     * отправляет все сообщения от пользователей на указанный URL.
     * 
     * После установки вебхука бот будет получать сообщения в реальном времени
     * на маршрут /api/telegram/webhook нашего приложения.
     * 
     * @return void
     */
    public function handle()
    {
        $argumentUrl = trim((string) ($this->argument('url') ?? ''));
        $configuredUrl = trim((string) config('telegram.bots.mybot.webhook_url'));
        $webhookUrl = $argumentUrl !== '' ? $argumentUrl : $configuredUrl;

        if ($webhookUrl === '') {
            $this->error('❌ URL вебхука не задан. Передайте аргументом или заполните TELEGRAM_WEBHOOK_URL в .env');
            return;
        }

        if (! filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            $this->error("❌ Некорректный URL вебхука: {$webhookUrl}");
            return;
        }
        
        try {
            $token = trim((string) config('telegram.bots.mybot.token'));
            if ($token === '') {
                $this->error('❌ TELEGRAM_BOT_TOKEN не задан в .env');
                return;
            }

            $setWebhookResponse = TelegramHttp::client()
                ->timeout(15)
                ->post(TelegramHttp::botMethodUrl($token, 'setWebhook'), [
                    'url' => $webhookUrl,
                ]);

            if (! $setWebhookResponse->ok()) {
                $this->error('❌ Ошибка setWebhook: ' . $setWebhookResponse->body());
                return;
            }
            
            // Выводим сообщение об успехе в консоль
            $this->info("✅ Вебхук успешно установлен!");
            $this->info("URL: {$webhookUrl}");
            
            // Проверяем текущий статус вебхука
            $infoResponse = TelegramHttp::client()
                ->timeout(15)
                ->get(TelegramHttp::botMethodUrl($token, 'getWebhookInfo'));

            $info = $infoResponse->json('result', []);
            
            // Выводим информацию о текущем вебхуке
            $currentUrl = $info['url'] ?? 'не установлен';
            $this->info("Текущий вебхук: {$currentUrl}");
            
            $this->info("Максимальное количество обновлений: " . ($info['max_connections'] ?? 'не указано'));
            $this->info("Ошибок: " . ($info['last_error_date'] ?? 0));
            
        } catch (\Exception $e) {
            // Если произошла ошибка, выводим сообщение об ошибке
            $this->error("❌ Ошибка при установке вебхука: " . $e->getMessage());
        }
    }
}
