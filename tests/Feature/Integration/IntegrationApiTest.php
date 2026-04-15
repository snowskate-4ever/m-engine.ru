<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\IntegrationApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class IntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_bearer_token(): void
    {
        $this->getJson('/api/integration/v1/me')->assertStatus(401);
    }

    public function test_mint_token_and_call_me(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $r = $this->postJson('/api/integration/tokens', ['name' => 'Test key']);
        $r->assertCreated();
        $plain = (string) $r->json('plain_token');

        $me = $this->getJson('/api/integration/v1/me', [
            'Authorization' => 'Bearer '.$plain,
        ]);
        $me->assertOk();
        $this->assertSame($user->id, (int) $me->json('user_id'));
    }

    public function test_analytics_summary_with_token(): void
    {
        $user = User::factory()->create();
        $minted = IntegrationApiToken::mint($user, 'k');

        $this->getJson('/api/integration/v1/analytics/bookings/summary', [
            'Authorization' => 'Bearer '.$minted['plain'],
        ])->assertOk()->assertJsonStructure(['total_bookings', 'with_search_request']);
    }
}
