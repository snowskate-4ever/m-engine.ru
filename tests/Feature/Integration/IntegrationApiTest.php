<?php

declare(strict_types=1);

namespace Tests\Feature\Integration;

use App\Models\IntegrationApiAuditLog;
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

    public function test_v2_me_requires_ability(): void
    {
        $user = User::factory()->create();
        $minted = IntegrationApiToken::mint($user, 'no-me', ['analytics:read']);

        $this->getJson('/api/integration/v2/me', [
            'Authorization' => 'Bearer '.$minted['plain'],
        ])->assertStatus(403);
    }

    public function test_v2_me_with_ability_works(): void
    {
        $user = User::factory()->create();
        $minted = IntegrationApiToken::mint($user, 'me', ['me:read']);

        $this->getJson('/api/integration/v2/me', [
            'Authorization' => 'Bearer '.$minted['plain'],
        ])->assertOk()->assertJsonPath('user_id', $user->id);
    }

    public function test_v2_me_writes_audit_log(): void
    {
        $user = User::factory()->create();
        $minted = IntegrationApiToken::mint($user, 'me', ['me:read']);

        $this->getJson('/api/integration/v2/me', [
            'Authorization' => 'Bearer '.$minted['plain'],
        ])->assertOk();

        $this->assertDatabaseHas('integration_api_audit_logs', [
            'integration_api_token_id' => $minted['token']->id,
            'user_id' => $user->id,
            'method' => 'GET',
            'status_code' => 200,
        ]);
        $this->assertSame(1, IntegrationApiAuditLog::query()->count());
    }

    public function test_v2_webhook_is_idempotent(): void
    {
        config()->set('integration.webhook_signature_secret', 'test_secret');
        $payload = ['booking_id' => 101];
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', (string) $raw, 'test_secret');

        $first = $this->postJson('/api/integration/v2/webhooks/events', $payload, [
            'Idempotency-Key' => 'idem_123',
            'X-Integration-Event' => 'booking.updated',
            'X-Integration-Signature' => $signature,
        ]);
        $first->assertCreated()->assertJsonPath('ok', true);

        $second = $this->postJson('/api/integration/v2/webhooks/events', $payload, [
            'Idempotency-Key' => 'idem_123',
            'X-Integration-Event' => 'booking.updated',
            'X-Integration-Signature' => $signature,
        ]);
        $second->assertOk()->assertJsonPath('duplicate', true);
    }

    public function test_v2_webhook_uses_dedicated_rate_limiter(): void
    {
        config()->set('integration.webhooks_rate_limit_per_minute', 2);
        config()->set('integration.webhook_signature_secret', '');

        $post = function (string $idem): \Illuminate\Testing\TestResponse {
            return $this->postJson('/api/integration/v2/webhooks/events', ['n' => $idem], [
                'Idempotency-Key' => $idem,
                'X-Integration-Event' => 'test.event',
            ]);
        };

        $post('w1')->assertCreated();
        $post('w2')->assertCreated();
        $post('w3')->assertStatus(429);
    }

    public function test_can_list_and_revoke_integration_tokens(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $minted = IntegrationApiToken::mint($user, 'api', ['me:read']);

        $this->getJson('/api/integration/tokens')
            ->assertOk()
            ->assertJsonPath('data.0.id', $minted['token']->id)
            ->assertJsonPath('data.0.name', 'api');

        $this->deleteJson('/api/integration/tokens/'.$minted['token']->id)->assertNoContent();

        $this->getJson('/api/integration/v2/me', [
            'Authorization' => 'Bearer '.$minted['plain'],
        ])->assertStatus(401);
    }

    public function test_cannot_revoke_foreign_integration_token(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $minted = IntegrationApiToken::mint($owner, 'secret');

        Sanctum::actingAs($other);
        $this->deleteJson('/api/integration/tokens/'.$minted['token']->id)->assertNotFound();
    }

    public function test_rotate_returns_new_plain_token_and_invalidates_old(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $old = IntegrationApiToken::mint($user, 'legacy', ['me:read', 'analytics:read']);

        $r = $this->postJson('/api/integration/tokens/'.$old['token']->id.'/rotate', []);
        $r->assertCreated();
        $newPlain = (string) $r->json('plain_token');
        $this->assertNotSame($old['plain'], $newPlain);
        $this->assertSame($old['token']->id, (int) $r->json('rotated_from_token_id'));

        $this->getJson('/api/integration/v2/me', [
            'Authorization' => 'Bearer '.$old['plain'],
        ])->assertStatus(401);

        $this->getJson('/api/integration/v2/me', [
            'Authorization' => 'Bearer '.$newPlain,
        ])->assertOk();
    }

    public function test_cannot_rotate_revoked_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $minted = IntegrationApiToken::mint($user, 'gone', ['me:read']);
        $minted['token']->forceFill(['revoked_at' => now()])->save();

        $this->postJson('/api/integration/tokens/'.$minted['token']->id.'/rotate', [])
            ->assertStatus(422);
    }
}
