<?php

namespace Tests\Feature\Shop;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopBotOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_connect_shop_bot_and_set_webapp_menu_button(): void
    {
        config()->set('app.frontend_url', 'https://e-tgo.ru');

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/getMe')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['username' => 'internet_magaz_dev_bot'],
                ], 200);
            }

            if (str_contains($request->url(), '/setChatMenuButton')) {
                return Http::response(['ok' => true, 'result' => true], 200);
            }

            return Http::response(['ok' => false], 404);
        });

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => 'Shop',
            'bot_token' => '123:abc',
            'delivery_name' => 'Самовывоз',
            'delivery_price' => 0,
        ]);

        $response = $this->postJson("/api/shops/{$shop->id}/bot-connect")
            ->assertOk()
            ->json('bot_setup');

        $this->assertTrue((bool) ($response['ok'] ?? false));
        $this->assertSame('https://e-tgo.ru/app?shop=' . $shop->id, $response['webapp_url'] ?? null);
        $this->assertSame('@internet_magaz_dev_bot', $response['bot_username'] ?? null);
        $this->assertTrue((bool) ($response['menu_button_set'] ?? false));
    }

    public function test_owner_can_get_bot_status_when_menu_button_not_set(): void
    {
        config()->set('app.frontend_url', 'https://e-tgo.ru');

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/getMe')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['username' => 'internet_magaz_dev_bot'],
                ], 200);
            }

            if (str_contains($request->url(), '/getChatMenuButton')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['type' => 'default'],
                ], 200);
            }

            return Http::response(['ok' => false], 404);
        });

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => 'Shop',
            'bot_token' => '123:abc',
            'delivery_name' => 'Самовывоз',
            'delivery_price' => 0,
        ]);

        $response = $this->getJson("/api/shops/{$shop->id}/bot-status")
            ->assertOk()
            ->json('bot_setup');

        $this->assertFalse((bool) ($response['ok'] ?? true));
        $this->assertSame('@internet_magaz_dev_bot', $response['bot_username'] ?? null);
        $this->assertFalse((bool) ($response['menu_button_set'] ?? true));
    }
}

