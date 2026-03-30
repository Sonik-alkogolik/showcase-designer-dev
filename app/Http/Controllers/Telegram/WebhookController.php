<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Контроллер для обработки вебхуков от бота Telegram
 * 
 * Этот контроллер принимает все входящие сообщения от бота,
 * анализирует их и выполняет соответствующие действия:
 * - Обработка команды /start {token} для привязки аккаунта
 * - Обработка команды /start без токена (инструкция)
 * 
 * Вебхук настраивается на маршрут /api/telegram/webhook
 */
class WebhookController extends Controller
{
    /**
     * Основной метод обработки входящих сообщений от бота
     * 
     * @param Request $request - HTTP запрос от Telegram
     * @return \Illuminate\Http\JsonResponse - Ответ в формате JSON
     */
    public function handle(Request $request)
    {
        $updates = $request->all();
        
        // Проверяем, есть ли в обновлениях сообщение от пользователя
        if (!isset($updates['message'])) {
            return response()->json(['ok' => true]);
        }
        
        // Извлекаем данные из сообщения
        $message = $updates['message'];
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $text = $message['text'] ?? '';

        if (preg_match('/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]+))?\s*$/', $text, $matches)) {
            $token = $matches[1] ?? null;
            if ($token) {
                // Обработка команды /start {token} - привязка аккаунта
                $this->handleLinkAccount($chatId, $token, $message['from'] ?? []);
                return response()->json(['ok' => true]);
            }

            // Обработка команды /start без токена
            $this->handleStartCommand($chatId);
            return response()->json(['ok' => true]);
        }
        
        // Стандартное сообщение для других команд
        $this->sendDefaultMessage($chatId);
        
        return response()->json(['ok' => true]);
    }

    /**
     * Обработка команды /start от пользователя
     * 
     * @param int $chatId - Уникальный идентификатор чата пользователя в Telegram
     * @return void
     */
    private function handleStartCommand($chatId)
    {
        $message = "👋 Привет! Я бот для привязки вашего аккаунта.\n\n" .
                   "Чтобы привязать Telegram к сайту:\n" .
                   "1. Зайдите в личный кабинет на сайте\n" .
                   "2. Нажмите 'Подключить Telegram'\n" .
                   "3. Перейдите по ссылке и отправьте команду /start с токеном";

        $this->sendMessage($chatId, $message);
    }
    
    /**
     * Обработка привязки аккаунта через токен
     * 
     * @param int $chatId - Уникальный идентификатор чата пользователя в Telegram
     * @param string $token - Токен для привязки из кэша
     * @param array $from - Данные пользователя из Telegram
     * @return void
     */
    private function handleLinkAccount($chatId, $token, $from)
    {
        // Получаем ID пользователя из кэша по токену
        $userId = Cache::get("telegram_link_{$token}");

        // Если токен не найден или устарел
        if (!$userId) {
            $this->sendMessage(
                $chatId,
                "❌ Ссылка устарела или недействительна.\n\n" .
                "Пожалуйста, сгенерируйте новую ссылку в личном кабинете на сайте."
            );
            return;
        }
        
        // Находим пользователя
        $user = User::find($userId);
        
        if (!$user) {
            $this->sendMessage($chatId, "❌ Пользователь не найден. Пожалуйста, попробуйте снова.");
            return;
        }

        // Проверяем, не привязан ли уже этот Telegram аккаунт к другому пользователю.
        // Повторная привязка тем же пользователем разрешена (id совпадает).
        $existingUser = User::where('telegram_id', $chatId)->first();
        if ($existingUser && (int) $existingUser->id !== (int) $user->id) {
            $this->sendMessage($chatId, "⚠️ Этот Telegram аккаунт уже привязан к другому пользователю.");
            return;
        }
        
        // Привязываем аккаунт
        $telegramUsername = $from['username'] ?? null;
        $user->linkTelegram($chatId, $telegramUsername);
        
        // Отправляем сообщение об успешной привязке
        $this->sendMessage(
            $chatId,
            "✅ Отлично! Ваш Telegram аккаунт успешно привязан к сайту.\n\n" .
            "Теперь вы будете получать уведомления о важных событиях."
        );
        
        // Удаляем токен из кэша
        Cache::forget("telegram_link_{$token}");
    }
    
    /**
     * Отправка стандартного сообщения с инструкциями
     * 
     * @param int $chatId - Уникальный идентификатор чата пользователя в Telegram
     * @return void
     */
    private function sendDefaultMessage($chatId)
    {
        $message = "Пожалуйста, введите /start для получения инструкций по привязке аккаунта.";
        $this->sendMessage($chatId, $message);
    }

    private function sendMessage(int $chatId, string $text): void
    {
        $token = trim((string) config('telegram.bots.mybot.token'));
        if ($token === '') {
            Log::warning('Telegram bot token is not configured, skip sendMessage');
            return;
        }

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
