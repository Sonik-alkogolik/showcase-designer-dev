<?php

namespace Tests\Feature\Support;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_support_ticket_with_context(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/support/tickets', [
            'category' => 'bot_problem',
            'preset' => 'Не получается прикрепить бота',
            'subject' => 'Не получается прикрепить бота',
            'message' => 'Кнопка подключения возвращает ошибку.',
            'current_url' => 'https://e-tgo.ru/shops/1/settings',
            'browser' => 'Feature Test Browser',
            'reported_at' => now()->toIso8601String(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('ticket.category', 'bot_problem')
            ->assertJsonPath('ticket.status', 'open')
            ->assertJsonCount(1, 'ticket.messages');

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'category' => 'bot_problem',
            'subject' => 'Не получается прикрепить бота',
            'current_url' => 'https://e-tgo.ru/shops/1/settings',
            'browser' => 'Feature Test Browser',
        ]);

        $this->assertDatabaseHas('support_ticket_messages', [
            'user_id' => $user->id,
            'sender_type' => 'user',
            'body' => 'Кнопка подключения возвращает ошибку.',
        ]);
    }

    public function test_user_can_only_open_own_ticket_history(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ticket = SupportTicket::query()->create([
            'user_id' => $owner->id,
            'user_email' => $owner->email,
            'category' => 'question',
            'subject' => 'Вопрос',
            'message' => 'Текст вопроса',
        ]);

        Sanctum::actingAs($otherUser);

        $this->getJson("/api/support/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_authenticated_user_can_reply_to_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'category' => 'question',
            'subject' => 'Вопрос по магазину',
            'message' => 'Первое сообщение',
            'status' => 'open',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/support/tickets/{$ticket->id}/messages", [
            'message' => 'Дополнительная информация от пользователя',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'ticket.messages');

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'sender_type' => 'user',
            'body' => 'Дополнительная информация от пользователя',
        ]);
    }
}
