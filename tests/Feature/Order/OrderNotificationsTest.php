<?php

namespace Tests\Feature\Order;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_sends_telegram_and_external_webhook(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
            'https://example.com/*' => Http::response(['ok' => true], 200),
        ]);

        $owner = User::factory()->create();
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Магазин',
            'bot_token' => '123456:test_bot_token',
            'notification_chat_id' => '-100100100',
            'delivery_name' => 'Курьер',
            'delivery_price' => 150,
            'webhook_url' => 'https://example.com/webhook/order',
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Товар',
            'price' => 550,
            'in_stock' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван',
            'phone' => '+79991112233',
            'items' => [
                ['id' => $product->id, 'quantity' => 2],
            ],
            'create_payment' => false,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $orderId = $response->json('order.id');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot123456:test_bot_token/sendMessage'
                && $request['chat_id'] === '-100100100';
        });

        Http::assertSent(function ($request) use ($orderId, $shop) {
            return $request->url() === 'https://example.com/webhook/order'
                && $request['event'] === 'order.created'
                && (int) $request['order']['id'] === (int) $orderId
                && (int) $request['order']['shop_id'] === (int) $shop->id;
        });
    }

    public function test_order_creation_can_resolve_notification_chat_id_by_username(): void
    {
        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'message' => [
                            'chat' => ['id' => 987654321],
                            'from' => ['username' => 'shop_owner'],
                        ],
                    ],
                ],
            ], 200),
            'https://api.telegram.org/*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $owner = User::factory()->create();
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Магазин',
            'bot_token' => '123456:test_bot_token',
            'notification_chat_id' => null,
            'notification_username' => '@shop_owner',
            'delivery_name' => 'Курьер',
            'delivery_price' => 150,
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Товар',
            'price' => 550,
            'in_stock' => true,
        ]);

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван',
            'phone' => '+79991112233',
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
            'create_payment' => false,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/getUpdates'));
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && (string) $request['chat_id'] === '987654321';
        });
    }
}
