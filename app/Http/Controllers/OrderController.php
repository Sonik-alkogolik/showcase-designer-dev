<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Получить список заказов магазина (для владельца)
     */
    public function index(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        $orders = $shop->orders()
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Создать новый заказ (публичный метод для Telegram Web App)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shop = Shop::findOrFail($request->shop_id);
        
        // Рассчитываем общую сумму
        $subtotal = 0;
        foreach ($request->items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $total = $subtotal + $shop->delivery_price;

        $order = Order::create([
            'shop_id' => $request->shop_id,
            'customer_name' => $request->customer_name,
            'phone' => $request->phone,
            'total' => $subtotal,
            'delivery_name' => $shop->delivery_name,
            'delivery_price' => $shop->delivery_price,
            'status' => 'pending',
            'items' => $request->items,
        ]);

        // TODO: Здесь будет интеграция с ЮKassa
        // Пока возвращаем тестовый ответ

        return response()->json([
            'success' => true,
            'message' => 'Заказ создан',
            'order' => $order,
            'payment_url' => 'https://example.com/pay/' . $order->id, // Заглушка
        ], 201);
    }

    /**
     * Получить информацию о заказе (для владельца)
     */
    public function show($shopId, $orderId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $order = $shop->orders()->findOrFail($orderId);
        
        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    /**
     * Обновить статус заказа (для владельца)
     */
    public function update(Request $request, $shopId, $orderId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $order = $shop->orders()->findOrFail($orderId);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Статус заказа обновлен',
            'order' => $order
        ]);
    }

    /**
     * Получить статус заказа по ID платежа (публичный)
     */
    public function checkPayment($paymentId)
    {
        $order = Order::where('yookassa_payment_id', $paymentId)->first();
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Заказ не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $order->status
        ]);
    }
}