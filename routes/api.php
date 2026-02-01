<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
    
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telegram_verified' => (bool) $user->telegram_verified_at,
            ]
        ]);
    });
});

// Защищённые маршруты (требуют авторизации и Telegram-верификации)
Route::middleware(['auth:sanctum', 'ensure.telegram.verified'])->group(function () {
    Route::get('/test-verification', function () {
        return response()->json(['message' => 'Доступ разрешён: Telegram подтверждён!']);
    });
});

// Маршрут для бота Telegram (публичный, без авторизации)
Route::post('/telegram/verify', function (Request $request) {
    // Валидация входных данных
    $request->validate([
        'code' => ['required', 'string', 'size:6'],
    ]);

    // Поиск пользователя по коду верификации
    $user = \App\Models\User::where('telegram_verification_code', $request->code)
        ->whereNull('telegram_verified_at')
        ->first();

    if (!$user) {
        return response()->json([
            'error' => 'Неверный или устаревший код верификации'
        ], 400);
    }

    // Помечаем пользователя как верифицированного
    $user->telegram_verified_at = now();
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Telegram успешно подтверждён!'
    ]);
});

Route::post('/telegram/webhook', [WebhookController::class, 'handle']);


// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Auth\RegisteredUserController;
// use App\Http\Controllers\Auth\AuthenticatedSessionController;

// // Публичные маршруты
// Route::post('/register', [RegisteredUserController::class, 'store']);
// Route::post('/login', [AuthenticatedSessionController::class, 'store']);
// Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
//     ->middleware('auth:sanctum');

// // Защищённые маршруты (требуют авторизации и Telegram-верификации)
// Route::middleware(['auth:sanctum', 'ensure.telegram.verified'])->group(function () {
//     Route::get('/test-verification', function () {
//         return response()->json(['message' => 'Доступ разрешён: Telegram подтверждён!']);
//     });
// });

// Route::get('/test-cors', function () {
//     return response()->json(['message' => 'CORS работает!']);
// });