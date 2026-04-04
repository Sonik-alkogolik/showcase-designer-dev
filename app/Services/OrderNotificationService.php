<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Shop;
use App\Support\TelegramHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderNotificationService
{
    public function notifyOrderCreated(Order $order): void
    {
        $shop = $order->shop;
        if (! $shop) {
            return;
        }

        $this->notifyTelegram($shop, $this->buildOrderCreatedText($order));
        $this->notifyExternalWebhook($shop, $order, 'order.created');
    }

    public function notifyOrderPaid(Order $order): void
    {
        $shop = $order->shop;
        if (! $shop) {
            return;
        }

        $this->notifyTelegram($shop, $this->buildOrderPaidText($order));
        $this->notifyExternalWebhook($shop, $order, 'order.paid');
    }

    private function buildOrderCreatedText(Order $order): string
    {
        return "Новый заказ №{$order->id} от {$order->customer_name}, сумма: {$order->total} ₽";
    }

    private function buildOrderPaidText(Order $order): string
    {
        return "Заказ №{$order->id} оплачен, сумма: {$order->total} ₽";
    }

    private function notifyTelegram(Shop $shop, string $text): void
    {
        if (blank($shop->bot_token)) {
            return;
        }

        $chatId = (string) ($shop->notification_chat_id ?? '');
        if (blank($chatId) && filled($shop->notification_username)) {
            $chatId = (string) $this->resolveChatIdByUsername($shop);
        }

        if (blank($chatId)) {
            return;
        }

        try {
            $response = TelegramHttp::client()
                ->timeout(10)
                ->asForm()
                ->post(TelegramHttp::botMethodUrl((string) $shop->bot_token, 'sendMessage'), [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);

            if (! $response->successful()) {
                Log::warning('Telegram notification failed', [
                    'shop_id' => $shop->id,
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram notification exception', [
                'shop_id' => $shop->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveChatIdByUsername(Shop $shop): ?string
    {
        $username = ltrim((string) $shop->notification_username, '@');
        if (blank($username) || blank($shop->bot_token)) {
            return null;
        }

        try {
            $response = TelegramHttp::client()
                ->timeout(10)
                ->get(TelegramHttp::botMethodUrl((string) $shop->bot_token, 'getUpdates'));
            if (! $response->ok()) {
                return null;
            }

            $updates = $response->json('result', []);
            if (! is_array($updates)) {
                return null;
            }

            foreach ($updates as $update) {
                $message = $update['message'] ?? null;
                if (! is_array($message)) {
                    continue;
                }

                $from = $message['from'] ?? [];
                $fromUsername = isset($from['username']) ? ltrim((string) $from['username'], '@') : '';
                if ($fromUsername !== $username) {
                    continue;
                }

                $chatId = $message['chat']['id'] ?? null;
                if ($chatId !== null) {
                    return (string) $chatId;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram getUpdates failed', [
                'shop_id' => $shop->id,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function notifyExternalWebhook(Shop $shop, Order $order, string $event): void
    {
        if (blank($shop->webhook_url)) {
            return;
        }

        $payload = [
            'event' => $event,
            'order' => [
                'id' => $order->id,
                'shop_id' => $order->shop_id,
                'customer_name' => $order->customer_name,
                'phone' => $order->phone,
                'total' => (float) $order->total,
                'delivery_name' => $order->delivery_name,
                'delivery_price' => (float) $order->delivery_price,
                'status' => $order->status,
                'items' => $order->items,
                'yookassa_payment_id' => $order->yookassa_payment_id,
                'created_at' => optional($order->created_at)->toISOString(),
                'updated_at' => optional($order->updated_at)->toISOString(),
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post((string) $shop->webhook_url, $payload);

            if (! $response->successful()) {
                Log::warning('Order external webhook failed', [
                    'shop_id' => $shop->id,
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Order external webhook exception', [
                'shop_id' => $shop->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
