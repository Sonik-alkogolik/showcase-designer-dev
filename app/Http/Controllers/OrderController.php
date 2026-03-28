<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Services\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use YooKassa\Client;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderNotificationService $orderNotificationService
    ) {
    }

    private function hasYookassaCredentials(): bool
    {
        return filled(config('services.yookassa.shop_id')) && filled(config('services.yookassa.secret_key'));
    }

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
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'create_payment' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $shop = Shop::findOrFail((int) $request->shop_id);
        $itemsPayload = collect($request->input('items', []));
        $productIds = $itemsPayload->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();

        $products = Product::query()
            ->where('shop_id', $shop->id)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'Некоторые товары не принадлежат этому магазину или не найдены.',
                'errors' => ['items' => ['Некорректный состав корзины.']],
            ], 422);
        }

        $subtotal = 0.0;
        $normalizedItems = [];
        foreach ($itemsPayload as $item) {
            $productId = (int) ($item['id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $product = $products->get($productId);

            if (! $product || $quantity < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Некорректный состав корзины.',
                    'errors' => ['items' => ['Некорректный состав корзины.']],
                ], 422);
            }

            if (! $product->in_stock) {
                return response()->json([
                    'success' => false,
                    'message' => "Товар \"{$product->name}\" недоступен.",
                    'errors' => ['items' => ["Товар \"{$product->name}\" отсутствует в наличии."]],
                ], 422);
            }

            $price = (float) $product->price;
            $lineTotal = $price * $quantity;
            $subtotal += $lineTotal;

            $normalizedItems[] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $price,
                'quantity' => $quantity,
                'line_total' => round($lineTotal, 2),
            ];
        }

        $deliveryPrice = (float) $shop->delivery_price;
        $total = round($subtotal + $deliveryPrice, 2);
        $wantsPayment = (bool) $request->boolean('create_payment', false);

        $order = Order::create([
            'shop_id' => $shop->id,
            'customer_name' => $request->customer_name,
            'phone' => $request->phone,
            'total' => round($subtotal, 2),
            'delivery_name' => $shop->delivery_name,
            'delivery_price' => $deliveryPrice,
            'status' => 'pending',
            'items' => $normalizedItems,
        ]);
        $this->orderNotificationService->notifyOrderCreated($order->fresh('shop'));

        if (! $wantsPayment) {
            return response()->json([
                'success' => true,
                'message' => 'Черновик заказа создан',
                'order' => $order->fresh(),
                'payment_id' => null,
                'confirmation_url' => null,
                'amounts' => [
                    'subtotal' => round($subtotal, 2),
                    'delivery' => round($deliveryPrice, 2),
                    'total' => $total,
                ],
            ], 201);
        }

        if (! $this->hasYookassaCredentials()) {
            return response()->json([
                'success' => false,
                'message' => 'ЮKassa не настроена для этого окружения.',
                'errors' => ['create_payment' => ['Невозможно создать платёж без ключей ЮKassa.']],
            ], 422);
        }

        try {
            $client = $this->yookassaClient();
            $idempotenceKey = (string) Str::uuid();

            $payment = $client->createPayment(
                [
                    'amount' => [
                        'value' => number_format($total, 2, '.', ''),
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
                'amounts' => [
                    'subtotal' => round($subtotal, 2),
                    'delivery' => round($deliveryPrice, 2),
                    'total' => $total,
                ],
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
        $incomingStatus = (string) $request->input('object.status');

        if (! in_array($event, ['payment.succeeded', 'payment.canceled'], true) || blank($paymentId)) {
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
            $providerStatus = (string) ($paymentInfo?->getStatus() ?? '');

            // Подтверждаем статус платежа через API провайдера, не только по payload вебхука.
            if ($incomingStatus !== '' && $providerStatus !== '' && $incomingStatus !== $providerStatus) {
                Log::warning('YooKassa webhook status mismatch', [
                    'payment_id' => $paymentId,
                    'order_id' => $order->id,
                    'incoming_status' => $incomingStatus,
                    'provider_status' => $providerStatus,
                ]);
            }

            if ($providerStatus === 'succeeded') {
                $wasPaid = $order->isPaid();
                $order->markAsPaid($paymentId);
                if (! $wasPaid) {
                    $this->orderNotificationService->notifyOrderPaid($order->fresh('shop'));
                }
            } elseif ($providerStatus === 'canceled') {
                $order->cancel();
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
