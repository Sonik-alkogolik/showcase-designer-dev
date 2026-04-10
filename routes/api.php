<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Telegram\WebhookController;
use App\Http\Controllers\ImportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Публичные маршруты
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
Route::get('/shops/{shop}/products/public', [App\Http\Controllers\ProductController::class, 'publicIndex']);
Route::get('/shops/{shop}/public', [App\Http\Controllers\ShopController::class, 'publicShow']);
// Тестовый маршрут для CORS
Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS работает!']);
});

// Защищённые маршруты (требуют только авторизации)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    
    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
    
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
    Route::get('/shops', [App\Http\Controllers\ShopController::class, 'index']);
    Route::post('/shops', [App\Http\Controllers\ShopController::class, 'store'])
        ->middleware(['ensure.telegram.verified', 'has.active.subscription']);
    Route::get('/shops/{shop}', [App\Http\Controllers\ShopController::class, 'show'])
        ->middleware('own.shop');
    Route::get('/shops/{shop}/bot-token', [App\Http\Controllers\ShopController::class, 'showBotToken'])
        ->middleware('own.shop');
    Route::get('/shops/{shop}/bot-status', [App\Http\Controllers\ShopController::class, 'botStatus'])
        ->middleware('own.shop');
    Route::post('/shops/{shop}/bot-connect', [App\Http\Controllers\ShopController::class, 'connectBot'])
        ->middleware('own.shop');
    Route::put('/shops/{shop}', [App\Http\Controllers\ShopController::class, 'update'])
        ->middleware('own.shop');
    Route::patch('/shops/{shop}', [App\Http\Controllers\ShopController::class, 'update'])
        ->middleware('own.shop');
    Route::delete('/shops/{shop}', [App\Http\Controllers\ShopController::class, 'destroy'])
        ->middleware('own.shop');

        // Маршруты для товаров
    Route::get('/shops/{shop}/products', [App\Http\Controllers\ProductController::class, 'index'])->middleware('own.shop');
    Route::post('/shops/{shop}/products', [App\Http\Controllers\ProductController::class, 'store'])->middleware('own.shop');
    Route::get('/shops/{shop}/products/{product}', [App\Http\Controllers\ProductController::class, 'show'])->middleware('own.shop');
    Route::put('/shops/{shop}/products/{product}', [App\Http\Controllers\ProductController::class, 'update'])->middleware('own.shop');
    Route::delete('/shops/{shop}/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->middleware('own.shop');
    // Route::post('/shops/{shop}/products/import', [App\Http\Controllers\ProductController::class, 'import']);
    Route::post('/shops/{shop}/import/preview', [ImportController::class, 'preview'])->middleware('own.shop');
    Route::post('/shops/{shop}/import', [ImportController::class, 'import'])->middleware('own.shop');
    Route::post('/shops/{shop}/import-products', [ImportController::class, 'import'])->middleware('own.shop');

        // Маршруты для категорий
    Route::get('/shops/{shop}/categories', [App\Http\Controllers\Api\CategoryController::class, 'index'])->middleware('own.shop');
    Route::post('/shops/{shop}/categories', [App\Http\Controllers\Api\CategoryController::class, 'store'])->middleware('own.shop');
    Route::get('/shops/{shop}/categories/{category}', [App\Http\Controllers\Api\CategoryController::class, 'show'])->middleware('own.shop');
    Route::put('/shops/{shop}/categories/{category}', [App\Http\Controllers\Api\CategoryController::class, 'update'])->middleware('own.shop');
    Route::delete('/shops/{shop}/categories/{category}', [App\Http\Controllers\Api\CategoryController::class, 'destroy'])->middleware('own.shop');
    Route::post('/shops/{shop}/categories/reorder', [App\Http\Controllers\Api\CategoryController::class, 'reorder'])->middleware('own.shop');

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

// Публичные маршруты для заказов (Telegram Web App)
Route::post('/orders', [App\Http\Controllers\OrderController::class, 'store'])
    ->middleware(['throttle:public-orders', 'verify.telegram.webapp']);
Route::get('/orders/payment/{paymentId}', [App\Http\Controllers\OrderController::class, 'checkPayment']);
Route::post('/webhooks/yookassa', [App\Http\Controllers\OrderController::class, 'yookassaWebhook']);

// Защищённые маршруты для заказов (для владельцев магазинов)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/shops/{shop}/orders', [App\Http\Controllers\OrderController::class, 'index'])->middleware('own.shop');
    Route::get('/shops/{shop}/orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->middleware('own.shop');
    Route::put('/shops/{shop}/orders/{order}', [App\Http\Controllers\OrderController::class, 'update'])->middleware('own.shop');
});
