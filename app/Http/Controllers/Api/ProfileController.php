<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Получение данных профиля пользователя.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        app(TelegramAvatarService::class)->ensureUserAvatar($user);
        $avatarRaw = (string) ($user->avatar ?? '');
        $avatarUrl = null;
        if ($avatarRaw !== '') {
            if (Str::startsWith($avatarRaw, ['http://', 'https://', '/'])) {
                $avatarUrl = $avatarRaw;
            } else {
                $avatarUrl = Storage::url($avatarRaw);
            }
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatarRaw !== '' ? $avatarRaw : null,
            'avatar_url' => $avatarUrl,
            'telegram_avatar_url' => $user->telegram_avatar_url,
            'telegram_linked' => $user->isTelegramLinked(),
            'telegram_id' => $user->telegram_id,
            'telegram_username' => $user->telegram_username,
            'telegram_linked_at' => $user->telegram_linked_at,
            'onboarding_completed_at' => $user->onboarding_completed_at,
            'requires_password_change' => (bool) $user->must_change_password,
        ]);
    }

    /**
     * Отметить onboarding как завершенный.
     */
    public function completeOnboarding(Request $request)
    {
        $user = $request->user();
        $user->onboarding_completed_at = now();
        $user->save();

        return response()->json([
            'success' => true,
            'onboarding_completed_at' => $user->onboarding_completed_at,
        ]);
    }

    /**
     * Сбросить onboarding (позволить показать обучение снова).
     */
    public function resetOnboarding(Request $request)
    {
        $user = $request->user();
        $user->onboarding_completed_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'onboarding_completed_at' => null,
        ]);
    }
    
    /**
     * Генерация токена для привязки Telegram.
     */
    public function generateTelegramLinkToken(Request $request)
    {
        $user = $request->user();

        // Генерируем уникальный токен
        $token = Str::random(32);

        // Сохраняем в кэше на 15 минут
        Cache::put("telegram_link_{$token}", $user->id, now()->addMinutes(15));

        $configuredBotUsername = trim((string) config('telegram.bots.mybot.username', 'constructor_app_bot'));
        $botUsername = ltrim($configuredBotUsername !== '' ? $configuredBotUsername : 'constructor_app_bot', '@');
        $botLink = "https://t.me/{$botUsername}?start={$token}";
        
        return response()->json([
            'token' => $token,
            'bot_username' => '@' . $botUsername,
            'bot_link' => $botLink,
            'expires_in' => 900, // 15 минут в секундах
        ]);
    }
    
    /**
     * Отвязка Telegram аккаунта.
     */
    public function unlinkTelegram(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isTelegramLinked()) {
            return response()->json([
                'message' => 'Telegram аккаунт не привязан'
            ], 400);
        }
        
        $user->unlinkTelegram();
        
        return response()->json([
            'message' => 'Telegram аккаунт успешно отвязан'
        ]);
    }

    /**
     * Удаление текущего аккаунта пользователя.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Удаляем все токены пользователя перед удалением аккаунта.
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Аккаунт успешно удален',
        ]);
    }
}
