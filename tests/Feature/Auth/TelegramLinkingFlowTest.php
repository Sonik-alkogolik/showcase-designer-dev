<?php

namespace Tests\Feature\Auth;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TelegramLinkingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_token_and_link_telegram_via_webhook(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $token = $this->postJson('/api/profile/telegram/generate-token')
            ->assertOk()
            ->json('token');

        $this->simulateTelegramStart($token, 777001, 'first_user');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_id' => 777001,
            'telegram_username' => 'first_user',
        ]);
    }

    public function test_generate_token_uses_configured_bot_username_in_link(): void
    {
        config()->set('telegram.bots.mybot.username', '@constructor_app_bot');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/profile/telegram/generate-token')
            ->assertOk()
            ->json();

        $this->assertSame('@constructor_app_bot', $response['bot_username'] ?? null);
        $this->assertStringContainsString('https://t.me/constructor_app_bot?start=', $response['bot_link'] ?? '');
    }

    public function test_same_user_can_relink_same_telegram_without_false_conflict(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'telegram_id' => 777002,
            'telegram_username' => 'old_name',
            'telegram_linked_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($user);

        $token = $this->postJson('/api/profile/telegram/generate-token')
            ->assertOk()
            ->json('token');

        $this->simulateTelegramStart($token, 777002, 'updated_name');

        $user->refresh();
        $this->assertSame('updated_name', $user->telegram_username);
        $this->assertTrue($user->isTelegramLinked());
    }

    public function test_cannot_link_telegram_if_chat_id_belongs_to_another_user(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        User::factory()->create([
            'telegram_id' => 777003,
            'telegram_username' => 'occupied_user',
            'telegram_linked_at' => now(),
        ]);

        $targetUser = User::factory()->create();
        Sanctum::actingAs($targetUser);

        $token = $this->postJson('/api/profile/telegram/generate-token')
            ->assertOk()
            ->json('token');

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 777003],
                'text' => '/start ' . $token,
                'from' => ['username' => 'new_user'],
            ],
        ])->assertOk();

        $targetUser->refresh();
        $this->assertNull($targetUser->telegram_id);
        $this->assertNull($targetUser->telegram_linked_at);

        Http::assertSent(function ($request): bool {
            $data = $request->data();
            return str_ends_with($request->url(), '/sendMessage')
                && isset($data['text'])
                && str_contains($data['text'], 'уже привязан');
        });
    }

    public function test_deleted_user_releases_telegram_for_new_user(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $oldUser = User::factory()->create([
            'telegram_id' => 777004,
            'telegram_username' => 'old_owner',
            'telegram_linked_at' => now(),
        ]);

        $oldUser->delete();

        $newUser = User::factory()->create();
        Sanctum::actingAs($newUser);

        $token = $this->postJson('/api/profile/telegram/generate-token')
            ->assertOk()
            ->json('token');

        $this->simulateTelegramStart($token, 777004, 'new_owner');

        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'telegram_id' => 777004,
            'telegram_username' => 'new_owner',
        ]);
    }

    public function test_simulated_user_cycles_with_register_link_shop_category_product_and_delete(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
        ]);

        // Имитация "удалить всех пользователей кроме админа"
        User::query()->whereKeyNot($admin->id)->delete();

        $telegramChatId = 777100;

        for ($cycle = 1; $cycle <= 3; $cycle++) {
            $email = "cycle{$cycle}@example.com";

            $registerResponse = $this->postJson('/api/register', [
                'name' => "Cycle User {$cycle}",
                'email' => $email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertCreated();

            $apiToken = $registerResponse->json('token');
            $this->assertNotEmpty($apiToken);

            $user = User::where('email', $email)->firstOrFail();
            Sanctum::actingAs($user);

            $generateResponse = $this->postJson('/api/profile/telegram/generate-token')
                ->assertOk();

            $linkToken = $generateResponse->json('token');
            $this->assertNotEmpty($linkToken, 'Empty telegram link token on cycle ' . $cycle);
            $this->assertSame(
                $user->id,
                Cache::get("telegram_link_{$linkToken}"),
                'Cache token does not map to current user on cycle ' . $cycle
            );
            // Для длинного e2e-цикла в sqlite :memory: связываем напрямую,
            // т.к. webhook-request может идти через отдельное соединение.
            $user->linkTelegram($telegramChatId, "cycle_user_{$cycle}");
            Cache::forget("telegram_link_{$linkToken}");

            $profile = $this->getJson('/api/profile')
                ->assertOk()
                ->json();

            $this->assertTrue(
                (bool) ($profile['telegram_linked'] ?? false),
                'Telegram not linked on cycle ' . $cycle . ': ' . json_encode($profile)
            );
            $this->assertSame($telegramChatId, (int) ($profile['telegram_id'] ?? 0));

            Subscription::create([
                'user_id' => $user->id,
                'plan' => 'starter',
                'status' => 'active',
                'expires_at' => now()->addDays(30),
                'auto_renew' => false,
                'price' => 490.00,
                'payment_method' => 'test',
            ]);

            $shopResponse = $this->postJson('/api/shops', [
                    'name' => "Shop {$cycle}",
                    'delivery_name' => 'Курьер',
                    'delivery_price' => 150,
                ])
                ->assertCreated();

            $shopId = $shopResponse->json('shop.id');
            $this->assertNotNull($shopId);

            $categoryResponse = $this->postJson("/api/shops/{$shopId}/categories", [
                    'name' => "Категория {$cycle}",
                ])
                ->assertCreated();

            $categoryId = $categoryResponse->json('category.id');
            $this->assertNotNull($categoryId);

            $this->postJson("/api/shops/{$shopId}/products", [
                    'name' => "Товар {$cycle}",
                    'price' => 1990,
                    'description' => 'Тестовый товар',
                    'category_id' => $categoryId,
                    'in_stock' => true,
                ])
                ->assertCreated();

            // Имитация удаления аккаунта пользователем через API.
            $this->deleteJson('/api/profile')
                ->assertOk();

            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        }

        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    private function simulateTelegramStart(string $token, int $chatId, string $username): void
    {
        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => $chatId],
                'text' => '/start ' . $token,
                'from' => ['username' => $username],
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $data = $request->data();
            return str_ends_with($request->url(), '/sendMessage')
                && isset($data['text'])
                && str_contains($data['text'], 'успешно привязан');
        });
    }
}
