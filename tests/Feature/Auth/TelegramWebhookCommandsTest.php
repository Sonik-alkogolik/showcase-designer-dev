<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookCommandsTest extends TestCase
{
    public function test_webhook_info_command_prints_fields(): void
    {
        config()->set('telegram.bots.mybot.token', '12345:test-token');

        Http::fake([
            'https://api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'url' => 'https://e-tgo.ru/api/telegram/webhook',
                    'pending_update_count' => 3,
                    'last_error_date' => 0,
                    'last_error_message' => '',
                    'max_connections' => 40,
                    'ip_address' => '1.2.3.4',
                ],
            ], 200),
        ]);

        $this->artisan('telegram:webhook-info')
            ->expectsOutput('Webhook URL: https://e-tgo.ru/api/telegram/webhook')
            ->expectsOutput('pending_update_count: 3')
            ->assertExitCode(0);
    }

    public function test_delete_webhook_command_calls_delete_and_info(): void
    {
        config()->set('telegram.bots.mybot.token', '12345:test-token');

        Http::fake([
            'https://api.telegram.org/*' => function ($request) {
                if (str_contains($request->url(), 'deleteWebhook')) {
                    return Http::response(['ok' => true, 'result' => true], 200);
                }

                if (str_contains($request->url(), 'getWebhookInfo')) {
                    return Http::response([
                        'ok' => true,
                        'result' => [
                            'url' => '',
                            'pending_update_count' => 0,
                            'last_error_date' => 0,
                            'last_error_message' => '',
                        ],
                    ], 200);
                }

                return Http::response(['ok' => false], 500);
            },
        ]);

        $this->artisan('telegram:delete-webhook')
            ->expectsOutput('✅ Webhook удален.')
            ->expectsOutput('drop_pending_updates: false')
            ->assertExitCode(0);
    }
}
