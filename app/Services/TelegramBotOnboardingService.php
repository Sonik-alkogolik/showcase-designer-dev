<?php

namespace App\Services;

use App\Models\Shop;
use App\Support\TelegramHttp;

class TelegramBotOnboardingService
{
    public function connectShopBot(Shop $shop): array
    {
        $token = trim((string) $shop->bot_token);
        if ($token === '') {
            return [
                'ok' => false,
                'message' => 'Токен бота не задан.',
                'webapp_url' => null,
                'bot_username' => null,
                'menu_button_set' => false,
                'domain_hint_required' => true,
            ];
        }

        $webAppUrl = $this->buildWebAppUrl($shop->id);
        $bot = $this->getBotIdentity($token);
        if (!($bot['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Не удалось проверить токен бота через getMe.',
                'webapp_url' => $webAppUrl,
                'bot_username' => null,
                'menu_button_set' => false,
                'domain_hint_required' => true,
            ];
        }

        $menuResult = $this->setChatMenuButton($token, $webAppUrl);
        if (!($menuResult['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Токен валиден, но не удалось установить кнопку WebApp.',
                'webapp_url' => $webAppUrl,
                'bot_username' => $bot['username'] ?? null,
                'menu_button_set' => false,
                'domain_hint_required' => true,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Бот подключен: токен валиден, WebApp-кнопка установлена.',
            'webapp_url' => $webAppUrl,
            'bot_username' => $bot['username'] ?? null,
            'menu_button_set' => true,
            'domain_hint_required' => true,
        ];
    }

    public function status(Shop $shop): array
    {
        $token = trim((string) $shop->bot_token);
        if ($token === '') {
            return [
                'ok' => false,
                'message' => 'Токен бота не задан.',
                'webapp_url' => null,
                'bot_username' => null,
                'menu_button_set' => false,
                'domain_hint_required' => true,
            ];
        }

        $webAppUrl = $this->buildWebAppUrl($shop->id);
        $bot = $this->getBotIdentity($token);
        if (!($bot['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Не удалось получить getMe по текущему токену.',
                'webapp_url' => $webAppUrl,
                'bot_username' => null,
                'menu_button_set' => false,
                'domain_hint_required' => true,
            ];
        }

        $menu = $this->getChatMenuButton($token);
        $menuButtonSet = (bool) (
            ($menu['ok'] ?? false)
            && data_get($menu, 'result.type') === 'web_app'
            && trim((string) data_get($menu, 'result.web_app.url', '')) === $webAppUrl
        );

        return [
            'ok' => $menuButtonSet,
            'message' => $menuButtonSet
                ? 'Бот готов к использованию.'
                : 'Нужна настройка WebApp-кнопки. Нажмите "Подключить бота".',
            'webapp_url' => $webAppUrl,
            'bot_username' => $bot['username'] ?? null,
            'menu_button_set' => $menuButtonSet,
            'domain_hint_required' => true,
        ];
    }

    private function buildWebAppUrl(int $shopId): string
    {
        $base = trim((string) config('app.frontend_url', ''));
        if ($base === '') {
            $base = trim((string) config('app.url', ''));
        }
        $base = rtrim($base, '/');

        return $base . '/app?shop=' . $shopId;
    }

    private function getBotIdentity(string $token): array
    {
        try {
            $response = TelegramHttp::client()
                ->timeout(12)
                ->get(TelegramHttp::botMethodUrl($token, 'getMe'));

            $data = $response->json();
            if (!$response->ok() || !(bool) data_get($data, 'ok', false)) {
                return ['ok' => false];
            }

            $username = trim((string) data_get($data, 'result.username', ''));
            return [
                'ok' => true,
                'username' => $username !== '' ? '@' . ltrim($username, '@') : null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false];
        }
    }

    private function setChatMenuButton(string $token, string $webAppUrl): array
    {
        try {
            $payload = [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => 'Открыть магазин',
                    'web_app' => [
                        'url' => $webAppUrl,
                    ],
                ],
            ];

            $response = TelegramHttp::client()
                ->timeout(12)
                ->post(TelegramHttp::botMethodUrl($token, 'setChatMenuButton'), $payload);

            $data = $response->json();
            return [
                'ok' => $response->ok() && (bool) data_get($data, 'ok', false),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false];
        }
    }

    private function getChatMenuButton(string $token): array
    {
        try {
            $response = TelegramHttp::client()
                ->timeout(12)
                ->get(TelegramHttp::botMethodUrl($token, 'getChatMenuButton'));

            $data = $response->json();
            return [
                'ok' => $response->ok() && (bool) data_get($data, 'ok', false),
                'result' => data_get($data, 'result'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'result' => null];
        }
    }
}
