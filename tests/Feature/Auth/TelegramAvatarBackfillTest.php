<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TelegramAvatarBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_request_backfills_telegram_avatar_for_linked_user(): void
    {
        config()->set('telegram.bots.mybot.token', '123:abc');

        $user = User::factory()->create([
            'telegram_id' => 954773719,
            'telegram_username' => 'vveb_front',
            'telegram_linked_at' => now(),
            'telegram_avatar_url' => null,
        ]);

        Sanctum::actingAs($user);

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/getUserProfilePhotos')) {
                return Http::response([
                    'ok' => true,
                    'result' => [
                        'photos' => [
                            [
                                ['file_id' => 'small'],
                                ['file_id' => 'large_file_id'],
                            ],
                        ],
                    ],
                ], 200);
            }

            if (str_contains($request->url(), '/getFile')) {
                return Http::response([
                    'ok' => true,
                    'result' => [
                        'file_path' => 'photos/avatar.jpg',
                    ],
                ], 200);
            }

            return Http::response(['ok' => false], 404);
        });

        $response = $this->getJson('/api/profile')
            ->assertOk()
            ->json();

        $this->assertSame(
            'https://api.telegram.org/file/bot123:abc/photos/avatar.jpg',
            $response['telegram_avatar_url'] ?? null
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_avatar_url' => 'https://api.telegram.org/file/bot123:abc/photos/avatar.jpg',
        ]);
    }
}

