<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки привязки аккаунта Telegram
 * 
 * Этот класс является промежуточным слоем (middleware) в Laravel,
 * который проверяет, привязан ли пользователь к своему аккаунту в Telegram.
 * 
 * Middleware используется для защиты маршрутов, которые требуют
 * обязательной привязки к Telegram (например, для получения уведомлений).
 * 
 * Если пользователь не привязан к Telegram, доступ к защищённым маршрутам
 * будет запрещён с кодом 403 (Forbidden).
 */
class EnsureTelegramVerified
{
    /**
     * Обработка входящего запроса
     * 
     * Этот метод вызывается каждый раз, когда запрос проходит через
     * данный middleware. Он проверяет условия и либо разрешает доступ
     * к маршруту, либо возвращает ошибку.
     * 
     * @param Request $request - Входящий HTTP запрос от клиента
     * @param Closure $next - Следующий обработчик в цепочке middleware
     * 
     * @return Response - HTTP ответ (либо ошибка, либо результат следующего обработчика)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ШАГ 1: Проверяем, авторизован ли пользователь
        // Если пользователь не авторизован (нет токена или сессии),
        // возвращаем ошибку 401 (Неавторизован)
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.'  // Пользователь не авторизован
            ], 401);  // Код статуса: 401 - Неавторизован
        }

        // ШАГ 2: Проверяем, привязан ли пользователь к Telegram
        // Вызываем метод isTelegramLinked() модели User,
        // который проверяет наличие telegram_id и telegram_linked_at
        if (! $request->user()->isTelegramLinked()) {
            // Если пользователь не привязан к Telegram,
            // возвращаем ошибку 403 (Запрещено) с дополнительной информацией
            return response()->json([
                'message' => 'Telegram account not linked.',  // Сообщение об ошибке
                'telegram_linked' => false,  // Флаг: аккаунт не привязан
            ], 403);  // Код статуса: 403 - Запрещено
        }

        // ШАГ 3: Если все проверки пройдены успешно,
        // передаём запрос следующему обработчику в цепочке
        // (обычно это контроллер, который обрабатывает маршрут)
        return $next($request);
    }
}