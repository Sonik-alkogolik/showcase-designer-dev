<?php

namespace App\Providers;

use App\Models\Shop;
use App\Policies\ShopPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Shop::class, ShopPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $key = $email.'|'.$request->ip();

            return Limit::perMinute((int) env('LOGIN_RATE_LIMIT_PER_MINUTE', 5))
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again later.',
                    ], 429);
                });
        });

        RateLimiter::for('public-orders', function (Request $request) {
            $shopId = (string) $request->input('shop_id', 'unknown');
            $key = $shopId.'|'.$request->ip();

            return Limit::perMinute((int) env('PUBLIC_ORDER_RATE_LIMIT_PER_MINUTE', 20))
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many order attempts. Please try again later.',
                    ], 429);
                });
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
