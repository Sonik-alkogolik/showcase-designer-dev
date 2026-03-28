<?php

namespace Tests\Feature\Shop;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_telegram_and_active_subscription_can_create_shop(): void
    {
        $user = User::factory()->create([
            'telegram_id' => 123456789,
            'telegram_username' => 'shop_test_user',
            'telegram_linked_at' => now(),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => 'starter',
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'auto_renew' => false,
            'price' => 490.00,
            'payment_method' => 'test',
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Тестовый магазин',
            'delivery_name' => 'Курьер',
            'delivery_price' => 199,
            'notification_chat_id' => '123456789',
            'notification_username' => '@shopowner',
            'webhook_url' => 'https://example.com/webhook/order',
        ];

        $response = $this->postJson('/api/shops', $payload);

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Магазин успешно создан',
                'shop' => [
                    'name' => 'Тестовый магазин',
                    'delivery_name' => 'Курьер',
                ],
            ]);

        $this->assertDatabaseHas('shops', [
            'user_id' => $user->id,
            'name' => 'Тестовый магазин',
            'delivery_name' => 'Курьер',
            'notification_chat_id' => '123456789',
            'notification_username' => '@shopowner',
            'webhook_url' => 'https://example.com/webhook/order',
        ]);
    }
}
