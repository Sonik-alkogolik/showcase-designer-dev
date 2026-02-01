<?php

namespace App\Console\Commands\Telegram;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class SetWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';
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
        // Формируем полный URL для вебхука
        // Например: https://ec-dn.ru:98/api/telegram/webhook
        $webhookUrl = 'https://ec-dn.ru/telegram-webhook-8240491675';
        
        try {
            // Устанавливаем вебхук через Telegram Bot API
            // Параметр 'url' указывает, куда Telegram будет отправлять сообщения
            Telegram::setWebhook(['url' => $webhookUrl]);
            
            // Выводим сообщение об успехе в консоль
            $this->info("✅ Вебхук успешно установлен!");
            $this->info("URL: {$webhookUrl}");
            
            // Проверяем текущий статус вебхука
            $info = Telegram::getWebhookInfo();
            
            // Выводим информацию о текущем вебхуке
            $currentUrl = $info['url'] ?? 'не установлен';
            $this->info("Текущий вебхук: {$currentUrl}");
            
            // Дополнительная информация о вебхуке (опционально)
            $this->info("Максимальное количество обновлений: " . ($info['max_connections'] ?? 'не указано'));
            $this->info("Ошибок: " . ($info['last_error_date'] ?? 0));
            
        } catch (\Exception $e) {
            // Если произошла ошибка, выводим сообщение об ошибке
            $this->error("❌ Ошибка при установке вебхука: " . $e->getMessage());
        }
    }
}