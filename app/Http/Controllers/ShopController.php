<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
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
            'delivery_name' => 'required|string|max:255',
            'delivery_price' => 'required|numeric|min:0',
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
            'delivery_name' => $request->delivery_name,
            'delivery_price' => $request->delivery_price,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Магазин успешно создан',
            'shop' => $shop,
            'remaining_shops' => $user->getRemainingShops()
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
        unset($shopData['bot_token']);
        
        return response()->json([
            'success' => true,
            'shop' => $shopData
        ]);
    }

    /**
     * Обновить магазин
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'bot_token' => 'nullable|string',
            'notification_chat_id' => 'nullable|string|max:255',
            'delivery_name' => 'sometimes|string|max:255',
            'delivery_price' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Если обновляется токен, проверяем его
        if ($request->has('bot_token')) {
            $newToken = $request->input('bot_token');

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

        $shop->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Магазин успешно обновлен',
            'shop' => $shop
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
        $shop = Shop::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'delivery_name' => $shop->delivery_name,
                'delivery_price' => $shop->delivery_price,
            ]
        ]);
    }
}
