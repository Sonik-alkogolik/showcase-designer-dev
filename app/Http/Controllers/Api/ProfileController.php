<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Получение данных профиля пользователя.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telegram_linked' => $user->isTelegramLinked(),
            'telegram_username' => $user->telegram_username,
            'telegram_linked_at' => $user->telegram_linked_at,
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
        
        return response()->json([
            'token' => $token,
            'bot_username' => '@constructor_app_bot',
            'bot_link' => "https://t.me/constructor_app_bot?start={$token}",
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
}