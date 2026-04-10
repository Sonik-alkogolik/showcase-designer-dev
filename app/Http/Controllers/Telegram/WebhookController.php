<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramMessageJob;
use App\Models\User;
use App\Support\TelegramHttp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $updateId = isset($updates['update_id']) ? (int) $updates['update_id'] : null;
        
        // Проверяем, есть ли в обновлениях сообщение от пользователя
        if (!isset($updates['message'])) {
            Log::debug('Telegram webhook: update without message', [
                'update_id' => $updateId,
                'keys' => array_keys((array) $updates),
            ]);
            return response()->json(['ok' => true]);
        }
        
        // Извлекаем данные из сообщения
        $message = $updates['message'];
        $chatId = (int) ($message['chat']['id'] ?? 0);
        $text = $message['text'] ?? '';
        $fromUsername = isset($message['from']['username']) ? (string) $message['from']['username'] : null;

        Log::info('Telegram webhook: message received', [
            'update_id' => $updateId,
            'chat_id' => $chatId,
            'from_username' => $fromUsername,
            'text_preview' => mb_substr((string) $text, 0, 120),
        ]);

        if (preg_match('/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]+))?\s*$/', $text, $matches)) {
            $token = $matches[1] ?? null;
            if ($token) {
                Log::info('Telegram webhook: /start with token', [
                    'update_id' => $updateId,
                    'chat_id' => $chatId,
                    'token_hint' => $this->tokenHint((string) $token),
                ]);
                // Обработка команды /start {token} - привязка аккаунта
                $this->handleLinkAccount($chatId, $token, $message['from'] ?? [], $updateId);
                return response()->json(['ok' => true]);
            }

            Log::info('Telegram webhook: /start without token', [
                'update_id' => $updateId,
                'chat_id' => $chatId,
            ]);
            // Обработка команды /start без токена
            $this->handleStartCommand($chatId);
            return response()->json(['ok' => true]);
        }
        
        Log::info('Telegram webhook: non-start command', [
            'update_id' => $updateId,
            'chat_id' => $chatId,
            'text_preview' => mb_substr((string) $text, 0, 120),
        ]);
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
    private function handleLinkAccount($chatId, $token, $from, ?int $updateId = null)
    {
        $cacheKey = "telegram_link_{$token}";
        // Получаем ID пользователя из кэша по токену
        $userId = Cache::get($cacheKey);

        // Если токен не найден или устарел
        if (!$userId) {
            Log::warning('Telegram link failed: token missing or expired', [
                'update_id' => $updateId,
                'chat_id' => (int) $chatId,
                'token_hint' => $this->tokenHint((string) $token),
                'cache_key' => $cacheKey,
            ]);
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
            Log::warning('Telegram link failed: user not found by token', [
                'update_id' => $updateId,
                'chat_id' => (int) $chatId,
                'token_hint' => $this->tokenHint((string) $token),
                'user_id_from_cache' => (int) $userId,
            ]);
            $this->sendMessage($chatId, "❌ Пользователь не найден. Пожалуйста, попробуйте снова.");
            return;
        }

        // Проверяем, не привязан ли уже этот Telegram аккаунт к другому пользователю.
        // Повторная привязка тем же пользователем разрешена (id совпадает).
        $existingUser = User::where('telegram_id', $chatId)->first();
        if ($existingUser && (int) $existingUser->id !== (int) $user->id) {
            Log::warning('Telegram link failed: chat_id already linked to another user', [
                'update_id' => $updateId,
                'chat_id' => (int) $chatId,
                'token_hint' => $this->tokenHint((string) $token),
                'target_user_id' => (int) $user->id,
                'existing_user_id' => (int) $existingUser->id,
            ]);
            $this->sendMessage($chatId, "⚠️ Этот Telegram аккаунт уже привязан к другому пользователю.");
            return;
        }
        
        // Привязываем аккаунт
        $telegramUsername = $from['username'] ?? null;
        $telegramAvatarUrl = $this->resolveTelegramAvatarUrl($chatId, $updateId);
        $user->linkTelegram($chatId, $telegramUsername, $telegramAvatarUrl);
        Log::info('Telegram link success', [
            'update_id' => $updateId,
            'chat_id' => (int) $chatId,
            'user_id' => (int) $user->id,
            'telegram_username' => $telegramUsername,
            'telegram_avatar_saved' => $telegramAvatarUrl !== null,
            'token_hint' => $this->tokenHint((string) $token),
        ]);
        
        // Отправляем сообщение об успешной привязке
        $this->sendMessage(
            $chatId,
            "✅ Отлично! Ваш Telegram аккаунт успешно привязан к сайту.\n\n" .
            "Теперь вы будете получать уведомления о важных событиях."
        );
        
        // Удаляем токен из кэша
        Cache::forget($cacheKey);
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
        SendTelegramMessageJob::dispatch($chatId, $text)->afterResponse();
    }

    private function tokenHint(string $token): string
    {
        $len = strlen($token);
        if ($len <= 8) {
            return $token;
        }

        return substr($token, 0, 4) . '...' . substr($token, -4);
    }

    private function resolveTelegramAvatarUrl(int $telegramId, ?int $updateId = null): ?string
    {
        $token = trim((string) config('telegram.bots.mybot.token'));
        if ($token === '' || $token === 'YOUR-BOT-TOKEN') {
            return null;
        }

        try {
            $photosResponse = TelegramHttp::client()
                ->timeout(8)
                ->get(TelegramHttp::botMethodUrl($token, 'getUserProfilePhotos'), [
                    'user_id' => $telegramId,
                    'limit' => 1,
                ]);

            if (! $photosResponse->ok() || ! (bool) data_get($photosResponse->json(), 'ok', false)) {
                return null;
            }

            $photoVariants = data_get($photosResponse->json(), 'result.photos.0');
            if (! is_array($photoVariants) || count($photoVariants) === 0) {
                return null;
            }

            $largestVariant = end($photoVariants);
            $fileId = is_array($largestVariant) ? (string) ($largestVariant['file_id'] ?? '') : '';
            if ($fileId === '') {
                return null;
            }

            $fileResponse = TelegramHttp::client()
                ->timeout(8)
                ->get(TelegramHttp::botMethodUrl($token, 'getFile'), [
                    'file_id' => $fileId,
                ]);

            if (! $fileResponse->ok() || ! (bool) data_get($fileResponse->json(), 'ok', false)) {
                return null;
            }

            $filePath = trim((string) data_get($fileResponse->json(), 'result.file_path', ''));
            if ($filePath === '') {
                return null;
            }

            return 'https://api.telegram.org/file/bot' . $token . '/' . ltrim($filePath, '/');
        } catch (\Throwable $e) {
            Log::warning('Telegram avatar resolve failed', [
                'update_id' => $updateId,
                'chat_id' => $telegramId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
