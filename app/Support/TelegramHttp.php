<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TelegramHttp
{
    public static function client(): PendingRequest
    {
        $request = Http::acceptJson();

        $proxy = trim((string) config('telegram.http_proxy', ''));
        if ($proxy !== '') {
            $request = $request->withOptions(['proxy' => $proxy]);
        }

        return $request;
    }

    public static function botMethodUrl(string $token, string $method): string
    {
        $baseBotUrl = trim((string) (config('telegram.base_bot_url') ?: 'https://api.telegram.org/bot'));

        return rtrim($baseBotUrl, '/') . $token . '/' . ltrim($method, '/');
    }
}
