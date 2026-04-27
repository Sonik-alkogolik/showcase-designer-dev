<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Получить список доступных тарифов
     */
    public function plans()
    {
        $plans = [
            'starter' => [
                'name' => 'Бесплатный',
                'price' => 0,
                'price_formatted' => '0 ₽/мес',
                'auto_renew' => false,
                'self_service' => true,
                'shops_limit' => 1,
                'products_limit' => 20,
                'can_import_excel' => false,
                'features' => [
                    '1 магазин',
                    'До 20 товаров',
                    'Telegram Web App'
                ],
                'popular' => false
            ],
            'business' => [
                'name' => 'Платный',
                'price' => 500,
                'price_formatted' => '500 ₽/мес',
                'auto_renew' => true,
                'self_service' => false,
                'contact_url' => 'https://t.me/vveb_front',
                'shops_limit' => 5,
                'products_limit' => 200,
                'can_import_excel' => true,
                'features' => [
                    '5 магазинов',
                    'До 200 товаров',
                    'Импорт товаров из Excel',
                    'Приоритетная поддержка'
                ],
                'popular' => true
            ],
        ];
        
        // Получаем текущую подписку пользователя, если он авторизован
        $currentSubscription = null;
        if (Auth::check()) {
            $currentSubscription = Auth::user()->activeSubscription()->first();
        }

        $currentCapabilities = Auth::check()
            ? Auth::user()->getPlanFeatures()
            : [
                'shops_limit' => 0,
                'products_limit' => 0,
                'can_import_excel' => false,
            ];
        
        return response()->json([
            'success' => true,
            'plans' => $plans,
            'current_subscription' => $currentSubscription,
            'current_capabilities' => $currentCapabilities,
        ]);
    }

    /**
     * Оформить подписку
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:starter,business',
            'auto_renew' => 'boolean',
            'offer_accepted' => 'required|accepted',
            'privacy_accepted' => 'required|accepted'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Необходимо авторизоваться'
            ], 401);
        }

        // Проверяем, нет ли уже активной подписки
        if ($user->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'У вас уже есть активная подписка'
            ], 400);
        }

        // Платный тариф переводим только через менеджера/админку.
        if ($request->plan !== 'starter') {
            return response()->json([
                'success' => false,
                'message' => 'Переход на платный тариф выполняется через менеджера',
                'contact_url' => 'https://t.me/vveb_front',
            ], 403);
        }

        // Цены в рублях
        $prices = [
            'starter' => 0,
            'business' => 500
        ];

        // Создаём подписку
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan' => $request->plan,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'auto_renew' => $request->auto_renew ?? false,
            'price' => $prices[$request->plan],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Подписка успешно оформлена',
            'subscription' => $subscription
        ]);
    }

    /**
     * Получить историю подписок пользователя
     */
    public function history()
    {
        $user = Auth::user();
        
        $subscriptions = $user->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions
        ]);
    }

    /**
     * Отменить подписку
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();
        $subscription = $user->activeSubscription()->first();
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Нет активной подписки'
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Подписка отменена'
        ]);
    }
}
