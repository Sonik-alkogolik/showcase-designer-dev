<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__ . '/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Только алиасы — для удобства
        $middleware->alias([
            'ensure.telegram.verified' => \App\Http\Middleware\EnsureTelegramVerified::class,
            'has.active.subscription' => \App\Http\Middleware\HasActiveSubscription::class,
            'own.shop' => \App\Http\Middleware\OwnsShop::class,
            'verify.telegram.webapp' => \App\Http\Middleware\VerifyTelegramWebAppInitData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
