<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebAppInitData
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('security.telegram_webapp.enforce', false)) {
            return $next($request);
        }

        $initData = trim((string) $request->header('X-Telegram-Init-Data', (string) $request->input('telegram_init_data', '')));
        if ($initData === '') {
            return response()->json([
                'success' => false,
                'message' => 'Telegram init data is required.',
            ], 403);
        }

        $shopId = (int) $request->input('shop_id');
        if ($shopId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shop_id for Telegram verification.',
            ], 422);
        }

        $shop = Shop::find($shopId);
        if (! $shop || blank($shop->bot_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram verification is not configured for this shop.',
            ], 403);
        }

        if (! $this->isValidInitData($initData, (string) $shop->bot_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Telegram init data signature.',
            ], 403);
        }

        return $next($request);
    }

    private function isValidInitData(string $initData, string $botToken): bool
    {
        parse_str($initData, $params);
        if (! is_array($params)) {
            return false;
        }

        $hash = (string) ($params['hash'] ?? '');
        if ($hash === '') {
            return false;
        }
        unset($params['hash']);

        $authDate = isset($params['auth_date']) ? (int) $params['auth_date'] : 0;
        $maxAgeSeconds = (int) config('security.telegram_webapp.max_age_seconds', 3600);
        if ($authDate <= 0 || abs(time() - $authDate) > $maxAgeSeconds) {
            return false;
        }

        ksort($params, SORT_STRING);
        $dataCheckString = collect($params)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode("\n");

        // Telegram WebApp validation:
        // secret_key = HMAC_SHA256(bot_token, "WebAppData")
        // hash = HMAC_SHA256(data_check_string, secret_key)
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($calculatedHash, $hash);
    }
}
