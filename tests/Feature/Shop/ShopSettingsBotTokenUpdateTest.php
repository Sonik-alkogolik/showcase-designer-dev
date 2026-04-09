<?php

namespace Tests\Feature\Shop;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopSettingsBotTokenUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_bot_token_in_update_does_not_clear_existing_token(): void
    {
        $user = User::factory()->create();
        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => 'Token Shop',
            'bot_token' => '111111:existing_token',
            'delivery_name' => 'Курьер',
            'delivery_price' => 100,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/shops/{$shop->id}", [
            'name' => 'Token Shop Updated',
            'bot_token' => null,
        ])->assertOk()->assertJson(['success' => true]);

        $shop->refresh();

        $this->assertSame('Token Shop Updated', $shop->name);
        $this->assertSame('111111:existing_token', $shop->bot_token);
    }

    public function test_new_bot_token_is_saved_when_validation_passes(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => 'Token Shop',
            'delivery_name' => 'Курьер',
            'delivery_price' => 100,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/shops/{$shop->id}", [
            'bot_token' => '222222:new_valid_token',
        ])->assertOk()->assertJson(['success' => true]);

        $shop->refresh();

        $this->assertSame('222222:new_valid_token', $shop->bot_token);
    }
}

