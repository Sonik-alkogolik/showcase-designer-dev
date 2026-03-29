<?php

namespace Tests\Feature\Security;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWebAppInitDataMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('security.telegram_webapp.enforce', true);
        config()->set('security.telegram_webapp.max_age_seconds', 3600);
    }

    private function createShopWithProduct(): array
    {
        $owner = User::factory()->create();
        $shop = Shop::create([
            'user_id' => $owner->id,
            'name' => 'Test TG Shop',
            'bot_token' => '123456:TEST_BOT_TOKEN',
            'delivery_name' => 'Курьер',
            'delivery_price' => 150,
        ]);

        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Товар',
            'price' => 500,
            'in_stock' => true,
        ]);

        return [$shop, $product];
    }

    private function buildInitData(string $botToken, int $authDateOffsetSeconds = 0): string
    {
        $params = [
            'auth_date' => (string) (time() + $authDateOffsetSeconds),
            'query_id' => 'AAEAAAE',
            'user' => json_encode([
                'id' => 123456789,
                'first_name' => 'Test',
                'username' => 'tester',
            ], JSON_UNESCAPED_UNICODE),
        ];

        ksort($params, SORT_STRING);
        $dataCheckString = collect($params)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);
        $params['hash'] = $hash;

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function test_orders_request_is_blocked_without_init_data_when_enforced(): void
    {
        [$shop, $product] = $this->createShopWithProduct();

        $response = $this->postJson('/api/orders', [
            'shop_id' => $shop->id,
            'customer_name' => 'Иван',
            'phone' => '+79991234567',
            'items' => [
                ['id' => $product->id, 'quantity' => 1],
            ],
            'create_payment' => false,
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', 'Telegram init data is required.');
    }

    public function test_orders_request_is_blocked_with_invalid_signature_when_enforced(): void
    {
        [$shop, $product] = $this->createShopWithProduct();

        $response = $this
            ->withHeader('X-Telegram-Init-Data', 'auth_date=1700000000&query_id=bad&hash=invalid')
            ->postJson('/api/orders', [
                'shop_id' => $shop->id,
                'customer_name' => 'Иван',
                'phone' => '+79991234567',
                'items' => [
                    ['id' => $product->id, 'quantity' => 1],
                ],
                'create_payment' => false,
            ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('message', 'Invalid Telegram init data signature.');
    }

    public function test_orders_request_passes_with_valid_init_data_when_enforced(): void
    {
        [$shop, $product] = $this->createShopWithProduct();
        $initData = $this->buildInitData('123456:TEST_BOT_TOKEN');

        $response = $this
            ->withHeader('X-Telegram-Init-Data', $initData)
            ->postJson('/api/orders', [
                'shop_id' => $shop->id,
                'customer_name' => 'Иван',
                'phone' => '+79991234567',
                'items' => [
                    ['id' => $product->id, 'quantity' => 2],
                ],
                'create_payment' => false,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.shop_id', $shop->id)
            ->assertJsonPath('order.items.0.id', $product->id);
    }
}
