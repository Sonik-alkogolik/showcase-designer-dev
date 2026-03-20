<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use YooKassa\Client;

class OrderController extends Controller
{
    private function yookassaClient(): Client
    {
        $shopId = config('services.yookassa.shop_id');
        $secretKey = config('services.yookassa.secret_key');

        if (blank($shopId) || blank($secretKey)) {
            throw new \RuntimeException('YooKassa credentials are not configured.');
        }

        $client = new Client();
        $client->setAuth((string) $shopId, (string) $secretKey);

        return $client;
    }

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

        try {
            $client = $this->yookassaClient();
            $idempotenceKey = (string) Str::uuid();

            $payment = $client->createPayment(
                [
                    'amount' => [
                        'value' => number_format((float) $total, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'payment_method_data' => [
                        'type' => 'bank_card',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => rtrim(config('app.frontend_url', config('app.url')), '/') . '/app?shop=' . $shop->id,
                    ],
                    'capture' => true,
                    'description' => 'Заказ №' . $order->id,
                    'metadata' => [
                        'order_id' => (string) $order->id,
                        'shop_id' => (string) $shop->id,
                    ],
                ],
                $idempotenceKey
            );

            $order->update([
                'yookassa_payment_id' => $payment?->getId(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заказ создан',
                'order' => $order->fresh(),
                'payment_id' => $payment?->getId(),
                'confirmation_url' => $payment?->getConfirmation()?->getConfirmationUrl(),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Failed to create YooKassa payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать платеж ЮKassa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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

    /**
     * Вебхук от YooKassa: обновление статуса заказа.
     */
    public function yookassaWebhook(Request $request)
    {
        $event = (string) $request->input('event');
        $paymentId = (string) $request->input('object.id');

        if ($event !== 'payment.succeeded' || blank($paymentId)) {
            return response()->json(['ok' => true]);
        }

        $order = Order::where('yookassa_payment_id', $paymentId)->first();

        if (! $order) {
            Log::warning('YooKassa webhook: order not found', [
                'payment_id' => $paymentId,
            ]);

            return response()->json(['ok' => true]);
        }

        try {
            $client = $this->yookassaClient();
            $paymentInfo = $client->getPaymentInfo($paymentId);

            if ($paymentInfo?->getStatus() === 'succeeded') {
                $order->markAsPaid($paymentId);
            }
        } catch (\Throwable $e) {
            Log::error('YooKassa webhook processing failed', [
                'payment_id' => $paymentId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
