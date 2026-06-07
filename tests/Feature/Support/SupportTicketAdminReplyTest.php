<?php

namespace Tests\Feature\Support;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketAdminReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_response_creates_message_and_updates_ticket_status(): void
    {
        $ticket = SupportTicket::query()->create([
            'user_id' => null,
            'user_email' => 'customer@example.com',
            'category' => 'question',
            'subject' => 'Вопрос по магазину',
            'message' => 'Нужна помощь с тикетом',
            'status' => 'open',
        ]);

        $ticket->update([
            'admin_response' => 'Здравствуйте, уже проверяем ваш тикет.',
        ]);

        $ticket->refresh();

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_name' => 'Администратор',
            'body' => 'Здравствуйте, уже проверяем ваш тикет.',
        ]);

        $this->assertSame('in_progress', $ticket->status);
        $this->assertNotNull($ticket->last_admin_replied_at);
    }
}
