<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_self_subscribe_to_business_plan(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/subscription/subscribe', [
            'plan' => 'business',
            'auto_renew' => true,
            'offer_accepted' => true,
            'privacy_accepted' => true,
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Переход на платный тариф выполняется через менеджера',
                'contact_url' => 'https://t.me/vveb_front',
            ]);
    }
}

