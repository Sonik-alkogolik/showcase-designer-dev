<?php

namespace App\Services;

use App\Models\Shop;
use App\Support\TelegramHttp;

class TelegramBotOnboardingService
{
    public function connectShopBot(Shop $shop): array
    {
        $token = trim((string) $shop->bot_token);
        $webAppUrl = $this->buildWebAppUrl($shop->id);

        if ($token === '') {
            return [
                'ok' => false,
                'message' => 'Токен бота не задан.',
                'error_code' => 'missing_token',
                'webapp_url' => $webAppUrl,
                'bot_username' => null,
                'telegram_api_reachable' => false,
                'menu_button_set' => false,
                'domain_hint_required' => true,
                'manual_setup' => $this->buildManualSetup($webAppUrl, null),
            ];
        }

        $bot = $this->getBotIdentity($token);
        if (!($bot['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Не удалось проверить токен бота через getMe (Telegram API недоступен или токен неверный).',
                'error_code' => 'telegram_api_unreachable_or_invalid_token',
                'webapp_url' => $webAppUrl,
                'bot_username' => null,
                'telegram_api_reachable' => false,
                'menu_button_set' => false,
                'domain_hint_required' => true,
                'manual_setup' => $this->buildManualSetup($webAppUrl, null),
            ];
        }

        $menuResult = $this->setChatMenuButton($token, $webAppUrl);
        if (!($menuResult['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Токен валиден, но не удалось установить кнопку WebApp.',
                'error_code' => 'set_menu_button_failed',
                'webapp_url' => $webAppUrl,
                'bot_username' => $bot['username'] ?? null,
                'telegram_api_reachable' => true,
                'menu_button_set' => false,
                'domain_hint_required' => true,
                'manual_setup' => $this->buildManualSetup($webAppUrl, $bot['username'] ?? null),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Бот подключен: токен валиден, WebApp-кнопка установлена.',
            'error_code' => null,
            'webapp_url' => $webAppUrl,
            'bot_username' => $bot['username'] ?? null,
            'telegram_api_reachable' => true,
            'menu_button_set' => true,
            'domain_hint_required' => true,
            'manual_setup' => $this->buildManualSetup($webAppUrl, $bot['username'] ?? null, false),
        ];
    }

    public function status(Shop $shop): array
    {
        $token = trim((string) $shop->bot_token);
        $webAppUrl = $this->buildWebAppUrl($shop->id);

        if ($token === '') {
            return [
                'ok' => false,
                'message' => 'Токен бота не задан.',
                'error_code' => 'missing_token',
                'webapp_url' => $webAppUrl,
                'bot_username' => null,
                'telegram_api_reachable' => false,
                'menu_button_set' => false,
                'domain_hint_required' => true,
                'manual_setup' => $this->buildManualSetup($webAppUrl, null),
            ];
        }

        $bot = $this->getBotIdentity($token);
        if (!($bot['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'Не удалось получить getMe по текущему токену.',
                'error_code' => 'telegram_api_unreachable_or_invalid_token',
                'webapp_url' => $webAppUrl,
                'bot_username' => null,
                'telegram_api_reachable' => false,
                'menu_button_set' => false,
                'domain_hint_required' => true,
                'manual_setup' => $this->buildManualSetup($webAppUrl, null),
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
            'error_code' => $menuButtonSet ? null : 'menu_button_not_configured',
            'webapp_url' => $webAppUrl,
            'bot_username' => $bot['username'] ?? null,
            'telegram_api_reachable' => (bool) ($menu['ok'] ?? false),
            'menu_button_set' => $menuButtonSet,
            'domain_hint_required' => true,
            'manual_setup' => $this->buildManualSetup($webAppUrl, $bot['username'] ?? null, !$menuButtonSet),
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

    private function buildManualSetup(string $webAppUrl, ?string $botUsername, bool $required = true): array
    {
        $domain = (string) parse_url($webAppUrl, PHP_URL_HOST);
        $normalizedBot = trim((string) $botUsername);
        $botLink = $normalizedBot !== ''
            ? 'https://t.me/' . ltrim($normalizedBot, '@')
            : null;

        return [
            'required' => $required,
            'domain' => $domain !== '' ? $domain : null,
            'bot_link' => $botLink,
            'steps' => [
                'Откройте @BotFather и выберите /mybots.',
                'Выберите вашего бота -> Bot Settings -> Menu Button.',
                'Нажмите Configure Menu Button и укажите: текст "Открыть магазин", URL "' . $webAppUrl . '".',
                'Выполните /setdomain и укажите домен "' . ($domain !== '' ? $domain : 'ваш_домен') . '".',
            ],
        ];
    }
}
