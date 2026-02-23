<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Telegram\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Публичные маршруты
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Тестовый маршрут для CORS
Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS работает!']);
});

// Защищённые маршруты (требуют только авторизации)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    
    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'show']);
    
    // Telegram привязка
    Route::post('/profile/telegram/generate-token', [ProfileController::class, 'generateTelegramLinkToken']);
    Route::delete('/profile/telegram/unlink', [ProfileController::class, 'unlinkTelegram']);
    
    // Совместимость: старый маршрут /user
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telegram_linked' => $user->isTelegramLinked(),
                'telegram_username' => $user->telegram_username,
            ]
        ]);
    });

    // Маршруты для подписок
    Route::get('/subscription/plans', [App\Http\Controllers\SubscriptionController::class, 'plans']);
    Route::post('/subscription/subscribe', [App\Http\Controllers\SubscriptionController::class, 'subscribe']);
    Route::get('/subscription/history', [App\Http\Controllers\SubscriptionController::class, 'history']);
    Route::post('/subscription/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel']);

    // Маршруты для магазинов
    Route::apiResource('shops', App\Http\Controllers\ShopController::class);
    Route::get('/shops/{shop}/public', [App\Http\Controllers\ShopController::class, 'publicShow']);
        // Маршруты для товаров
    Route::get('/shops/{shop}/products', [App\Http\Controllers\ProductController::class, 'index']);
    Route::post('/shops/{shop}/products', [App\Http\Controllers\ProductController::class, 'store']);
    Route::get('/shops/{shop}/products/{product}', [App\Http\Controllers\ProductController::class, 'show']);
    Route::put('/shops/{shop}/products/{product}', [App\Http\Controllers\ProductController::class, 'update']);
    Route::delete('/shops/{shop}/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy']);
    Route::post('/shops/{shop}/products/import', [App\Http\Controllers\ProductController::class, 'import']);


});

// Защищённые маршруты (требуют авторизации и привязки Telegram)
Route::middleware(['auth:sanctum', 'ensure.telegram.verified'])->group(function () {
    Route::get('/test-verification', function () {
        return response()->json(['message' => 'Доступ разрешён: Telegram привязан!']);
    });
    
});

// Маршрут для бота Telegram (публичный, без авторизации)
Route::post('/telegram/webhook', [WebhookController::class, 'handle']);
// Публичные маршруты для Telegram Web App
Route::get('/shops/{shop}/products/public', [App\Http\Controllers\ProductController::class, 'publicIndex']);

