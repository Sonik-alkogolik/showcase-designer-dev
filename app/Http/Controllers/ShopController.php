<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Services\TelegramBotOnboardingService;
use App\Services\TelegramAvatarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function __construct(
        private readonly TelegramBotOnboardingService $telegramBotOnboardingService
    ) {
    }

    /**
     * Получить список магазинов пользователя
     */
    public function index()
    {
        $user = Auth::user();
        $shops = $user->shops()->get();
        
        return response()->json([
            'success' => true,
            'shops' => $shops
        ]);
    }

    /**
     * Создать новый магазин
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Проверяем, может ли пользователь создать магазин
        if (!$user->canCreateMoreShops()) {
            return response()->json([
                'success' => false,
                'message' => 'Вы достигли лимита магазинов для вашего тарифа',
                'remaining' => 0,
                'limit' => $user->getShopsLimit()
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bot_token' => 'nullable|string',
            'notification_chat_id' => 'nullable|string|max:255',
            'notification_username' => ['nullable', 'string', 'max:255', 'regex:/^@?[A-Za-z0-9_]{5,}$/'],
            'delivery_name' => 'required|string|max:255',
            'delivery_price' => 'required|numeric|min:0',
            'webhook_url' => 'nullable|url|max:2048',
            'theme_settings' => 'nullable|array',
            'theme_settings.background_start' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_settings.background_end' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_settings.text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_settings.dots_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Если указан токен бота, проверяем его
        if ($request->bot_token) {
            $shop = new Shop($request->all());
            $shop->bot_token = $request->bot_token; // Токен зашифруется автоматически в мутаторе
            if (!$shop->validateBotToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный токен Telegram бота'
                ], 422);
            }
        }

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'bot_token' => $request->bot_token,
            'notification_chat_id' => $request->notification_chat_id,
            'notification_username' => $request->notification_username,
            'delivery_name' => $request->delivery_name,
            'delivery_price' => $request->delivery_price,
            'webhook_url' => $request->webhook_url,
            'theme_settings' => $this->normalizeThemeSettings($request->input('theme_settings')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Магазин успешно создан',
            'shop' => $shop,
            'remaining_shops' => $user->getRemainingShops(),
            'bot_setup' => $request->filled('bot_token')
                ? $this->telegramBotOnboardingService->connectShopBot($shop)
                : null,
        ], 201);
    }

    /**
     * Получить информацию о магазине
     */
    public function show($id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);

        // Не показываем токен в ответе
        $shopData = $shop->toArray();
        $shopData['has_bot_token'] = ! empty($shop->getRawOriginal('bot_token'));
        $shopData['theme_settings'] = $this->normalizeThemeSettings($shop->theme_settings);
        unset($shopData['bot_token']);

        return response()->json([
            'success' => true,
            'shop' => $shopData
        ]);
    }

    /**
     * Получить bot token магазина (только для владельца, по явному запросу UI)
     */
    public function showBotToken($id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);

        return response()->json([
            'success' => true,
            'bot_token' => $shop->bot_token,
        ]);
    }

    /**
     * Обновить магазин
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);
        $payload = $request->all();

        $validator = Validator::make($payload, [
            'name' => 'sometimes|string|max:255',
            'bot_token' => 'nullable|string',
            'notification_chat_id' => 'nullable|string|max:255',
            'notification_username' => ['nullable', 'string', 'max:255', 'regex:/^@?[A-Za-z0-9_]{5,}$/'],
            'delivery_name' => 'sometimes|string|max:255',
            'delivery_price' => 'sometimes|numeric|min:0',
            'webhook_url' => 'nullable|url|max:2048',
            'theme_settings' => 'nullable|array',
            'theme_settings.background_start' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_settings.background_end' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_settings.text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'theme_settings.dots_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Если обновляется токен, проверяем его
        if (array_key_exists('bot_token', $payload)) {
            $newToken = $payload['bot_token'];

            // Пустой токен в форме означает "не менять текущий токен"
            if (blank($newToken)) {
                unset($payload['bot_token']);
            }

            if (filled($newToken) && $newToken !== $shop->bot_token) {
                $tempShop = new Shop(['bot_token' => $newToken]);
                if (!$tempShop->validateBotToken()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Неверный токен Telegram бота'
                    ], 422);
                }
            }
        }

        if (array_key_exists('theme_settings', $payload)) {
            $payload['theme_settings'] = $this->normalizeThemeSettings($payload['theme_settings']);
        }

        $shop->update($payload);

        $shopData = $shop->toArray();
        $shopData['has_bot_token'] = ! empty($shop->getRawOriginal('bot_token'));
        unset($shopData['bot_token']);

        return response()->json([
            'success' => true,
            'message' => 'Магазин успешно обновлен',
            'shop' => $shopData,
            'bot_setup' => array_key_exists('bot_token', $payload)
                ? $this->telegramBotOnboardingService->connectShopBot($shop)
                : null,
        ]);
    }

    /**
     * Автоподключение витринного бота: getMe + setChatMenuButton
     */
    public function connectBot($id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);

        $result = $this->telegramBotOnboardingService->connectShopBot($shop);
        $statusCode = ($result['ok'] ?? false) ? 200 : 422;

        return response()->json([
            'success' => (bool) ($result['ok'] ?? false),
            'bot_setup' => $result,
        ], $statusCode);
    }

    /**
     * Статус готовности витринного бота.
     */
    public function botStatus($id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);

        $result = $this->telegramBotOnboardingService->status($shop);

        return response()->json([
            'success' => true,
            'bot_setup' => $result,
        ]);
    }

    /**
     * Удалить магазин
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);
        
        $shop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Магазин успешно удален',
            'remaining_shops' => $user->getRemainingShops()
        ]);
    }

    /**
     * Получить публичную информацию о магазине (для Telegram Web App)
     */
    public function publicShow($id)
    {
        $shop = Shop::with('user')->findOrFail($id);
        $managerUsername = $shop->notification_username
            ? ltrim((string) $shop->notification_username, '@')
            : null;
        $owner = $shop->user;
        if ($owner) {
            app(TelegramAvatarService::class)->ensureUserAvatar($owner);
        }

        $ownerAvatarRaw = (string) ($owner?->avatar ?? '');
        $ownerTelegramAvatarUrl = trim((string) ($owner?->telegram_avatar_url ?? ''));
        $ownerAvatarUrl = null;
        if ($ownerAvatarRaw !== '') {
            if (str_starts_with($ownerAvatarRaw, 'http://') || str_starts_with($ownerAvatarRaw, 'https://') || str_starts_with($ownerAvatarRaw, '/')) {
                $ownerAvatarUrl = $ownerAvatarRaw;
            } else {
                $ownerAvatarUrl = Storage::url($ownerAvatarRaw);
            }
        }
        if ($ownerAvatarUrl === null && $ownerTelegramAvatarUrl !== '') {
            $ownerAvatarUrl = $ownerTelegramAvatarUrl;
        }
        
        return response()->json([
            'success' => true,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'delivery_name' => $shop->delivery_name,
                'delivery_price' => $shop->delivery_price,
                'manager_telegram_username' => $managerUsername,
                'manager_telegram_url' => $managerUsername ? 'https://t.me/' . $managerUsername : null,
                'theme_settings' => $this->normalizeThemeSettings($shop->theme_settings),
                'owner_profile' => [
                    'name' => $owner?->name,
                    'email' => $owner?->email,
                    'telegram_username' => $owner?->telegram_username,
                    'telegram_avatar_url' => $ownerTelegramAvatarUrl !== '' ? $ownerTelegramAvatarUrl : null,
                    'avatar_url' => $ownerAvatarUrl,
                ],
            ]
        ]);
    }

    private function normalizeThemeSettings(mixed $themeSettings): array
    {
        $defaults = [
            'background_start' => '#070B18',
            'background_end' => '#0D1326',
            'text_color' => '#EFF6FF',
            'dots_color' => '#38E8FF',
        ];

        if (! is_array($themeSettings)) {
            return $defaults;
        }

        $normalized = $defaults;
        foreach ($defaults as $key => $defaultValue) {
            $candidate = strtoupper(trim((string) ($themeSettings[$key] ?? '')));
            if (preg_match('/^#([A-F0-9]{6})$/', $candidate) === 1) {
                $normalized[$key] = $candidate;
            }
        }

        return $normalized;
    }
}
