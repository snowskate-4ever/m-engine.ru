<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Enums\AiRequestSource;
use App\Enums\UserAiSubscriptionStatus;
use App\Models\AiProvider;
use App\Models\AiServerModel;
use App\Models\AiSubscriptionTier;
use App\Models\AiUsageLedger;
use App\Models\User;
use App\Models\UserAiSubscription;
use App\Contracts\Billing\PaymentGatewayContract;
use App\Services\Ai\AiServerQuotaDeniedException;
use App\Services\Ai\AiServerQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AiBillingSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_endpoint_requires_auth(): void
    {
        config(['ai.enabled' => true]);

        $this->getJson('/api/ai/subscription')->assertUnauthorized();
    }

    public function test_subscription_endpoint_returns_payload(): void
    {
        config(['ai.enabled' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $r = $this->getJson('/api/ai/subscription');
        $r->assertOk()
            ->assertJsonPath('subscription', null)
            ->assertJsonStructure([
                'legacy_subscription_valid_until',
                'subscription',
                'preferences',
                'server_ai' => [
                    'unlimited_daily_requests',
                    'daily_request_cap',
                    'allowed_server_model_ids',
                    'quota_timezone',
                    'usage_date',
                    'server_requests_used_today',
                    'server_requests_daily_effective_limit',
                    'server_requests_remaining_today',
                    'server_side_daily_ceiling',
                    'server_tokens_monthly_cap',
                    'server_tokens_used_this_month',
                    'server_tokens_remaining_this_month',
                ],
                'client_hints' => [
                    'needs_subscription',
                    'server_ai_available',
                ],
            ]);
    }

    public function test_stub_webhook_rejects_without_secret(): void
    {
        config(['billing.webhook_secret' => '']);

        $this->call(
            'POST',
            '/api/webhooks/billing/stub',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
            '{}',
        )->assertForbidden();
    }

    public function test_stub_webhook_creates_subscription_with_valid_hmac(): void
    {
        config(['billing.webhook_secret' => 'whsec_test']);
        $user = User::factory()->create();
        AiSubscriptionTier::query()->create([
            'slug' => 'test_tier',
            'name' => 'Test',
            'description' => null,
            'price_monthly_rub' => null,
            'internal_reference_cost_monthly' => null,
            'limits' => ['server_requests_per_day' => 5],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $payload = [
            'user_id' => $user->id,
            'tier_slug' => 'test_tier',
            'current_period_end' => now()->addMonth()->toIso8601String(),
            'sync_user_valid_until' => false,
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'whsec_test');

        $this->call(
            'POST',
            '/api/webhooks/billing/stub',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Billing-Signature' => $sig,
            ]),
            $body,
        )->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('user_ai_subscriptions', [
            'user_id' => $user->id,
            'status' => UserAiSubscriptionStatus::Active->value,
        ]);
    }

    public function test_stub_webhook_idempotent_by_external_payment_ref(): void
    {
        config(['billing.webhook_secret' => 'whsec_test']);
        $user = User::factory()->create();
        AiSubscriptionTier::query()->create([
            'slug' => 'test_tier',
            'name' => 'Test',
            'description' => null,
            'price_monthly_rub' => null,
            'internal_reference_cost_monthly' => null,
            'limits' => ['server_requests_per_day' => 5],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $payload = [
            'user_id' => $user->id,
            'tier_slug' => 'test_tier',
            'current_period_end' => now()->addMonth()->toIso8601String(),
            'sync_user_valid_until' => false,
            'payment_provider' => 'stub',
            'external_payment_ref' => 'ext_pay_1',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'whsec_test');
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Billing-Signature' => $sig,
        ];

        $this->call('POST', '/api/webhooks/billing/stub', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();
        $this->call('POST', '/api/webhooks/billing/stub', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();

        $this->assertSame(1, UserAiSubscription::query()->where('external_payment_ref', 'ext_pay_1')->count());
    }

    public function test_stub_webhook_deduplicates_by_webhook_event_id(): void
    {
        config(['billing.webhook_secret' => 'whsec_test']);
        $user = User::factory()->create();
        AiSubscriptionTier::query()->create([
            'slug' => 'test_tier',
            'name' => 'Test',
            'description' => null,
            'price_monthly_rub' => null,
            'internal_reference_cost_monthly' => null,
            'limits' => ['server_requests_per_day' => 5],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $payload = [
            'user_id' => $user->id,
            'tier_slug' => 'test_tier',
            'current_period_end' => now()->addMonth()->toIso8601String(),
            'sync_user_valid_until' => false,
            'webhook_event_id' => 'evt_dup_1',
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'whsec_test');
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Billing-Signature' => $sig,
        ];

        $id1 = $this->call('POST', '/api/webhooks/billing/stub', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk()
            ->json('subscription_id');
        $r2 = $this->call('POST', '/api/webhooks/billing/stub', [], [], [], $this->transformHeadersToServerVars($headers), $body)
            ->assertOk();
        $this->assertTrue($r2->json('deduplicated'));
        $this->assertSame($id1, $r2->json('subscription_id'));
    }

    public function test_yookassa_webhook_verifies_payment_and_creates_subscription(): void
    {
        config([
            'billing.yookassa.shop_id' => '123456',
            'billing.yookassa.secret_key' => 'test_secret',
        ]);
        $user = User::factory()->create();
        AiSubscriptionTier::query()->create([
            'slug' => 'yk_tier',
            'name' => 'YooKassa tier',
            'description' => null,
            'price_monthly_rub' => null,
            'internal_reference_cost_monthly' => null,
            'limits' => ['server_requests_per_day' => 10],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $periodEnd = now()->addMonth()->toIso8601String();
        Http::fake([
            'https://api.yookassa.ru/v3/payments/pay_yk_1' => Http::response([
                'id' => 'pay_yk_1',
                'status' => 'succeeded',
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'tier_slug' => 'yk_tier',
                    'current_period_end' => $periodEnd,
                ],
            ], 200),
        ]);

        $notification = [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => ['id' => 'pay_yk_1'],
        ];

        $this->postJson('/api/webhooks/billing/yookassa', $notification)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('user_ai_subscriptions', [
            'user_id' => $user->id,
            'payment_provider' => 'yookassa',
            'external_payment_ref' => 'pay_yk_1',
            'status' => UserAiSubscriptionStatus::Active->value,
        ]);
    }

    public function test_payment_gateway_resolves_yookassa_from_config(): void
    {
        config(['billing.payment_gateway' => 'yookassa']);
        $gw = app(PaymentGatewayContract::class);
        $this->assertSame('yookassa', $gw->key());
    }

    public function test_tier_daily_cap_blocks_after_consumption(): void
    {
        $user = User::factory()->create();
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'cap1',
            'name' => 'Cap 1',
            'limits' => ['server_requests_per_day' => 1],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserAiSubscription::query()->create([
            'user_id' => $user->id,
            'ai_subscription_tier_id' => $tier->id,
            'status' => UserAiSubscriptionStatus::Active,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'payment_provider' => 'test',
            'external_payment_ref' => null,
            'cancel_at_period_end' => false,
        ]);

        $svc = app(AiServerQuotaService::class);
        $svc->assertMayConsumeServerAiRequest($user);
        $svc->recordSuccessfulServerAiRequest($user);

        $this->expectException(AiServerQuotaDeniedException::class);
        $svc->assertMayConsumeServerAiRequest($user->fresh());
    }

    public function test_model_allowlist_denies_other_model(): void
    {
        $user = User::factory()->create();
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'models',
            'name' => 'Models',
            'limits' => [
                'server_requests_per_day' => null,
                'allowed_ai_server_model_ids' => [99],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserAiSubscription::query()->create([
            'user_id' => $user->id,
            'ai_subscription_tier_id' => $tier->id,
            'status' => UserAiSubscriptionStatus::Active,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'payment_provider' => 'test',
            'external_payment_ref' => null,
            'cancel_at_period_end' => false,
        ]);

        $svc = app(AiServerQuotaService::class);

        $this->expectException(AiServerQuotaDeniedException::class);
        $svc->assertServerModelAllowedForPlan($user, 100);
    }

    public function test_server_models_list_respects_subscription_allowlist(): void
    {
        config(['ai.enabled' => true]);
        $user = User::factory()->create();
        $provider = AiProvider::query()->create([
            'name' => 'Test',
            'driver' => 'openai',
            'config' => [],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $mKeep = AiServerModel::query()->create([
            'ai_provider_id' => $provider->id,
            'vendor_model_id' => 'gpt-4o',
            'display_name' => 'Keep',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        AiServerModel::query()->create([
            'ai_provider_id' => $provider->id,
            'vendor_model_id' => 'gpt-4o-mini',
            'display_name' => 'Hide',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'allow_one',
            'name' => 'One model',
            'limits' => ['allowed_ai_server_model_ids' => [$mKeep->id]],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserAiSubscription::query()->create([
            'user_id' => $user->id,
            'ai_subscription_tier_id' => $tier->id,
            'status' => UserAiSubscriptionStatus::Active,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'payment_provider' => 'test',
            'external_payment_ref' => null,
            'cancel_at_period_end' => false,
        ]);

        Sanctum::actingAs($user);
        $ids = collect($this->getJson('/api/ai/server-models')->assertOk()->json('data'))
            ->pluck('id')
            ->all();
        $this->assertSame([$mKeep->id], $ids);
    }

    public function test_ai_preferences_self_cap_cannot_exceed_tier_daily_cap(): void
    {
        config(['ai.enabled' => true]);
        $user = User::factory()->create();
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'cap5',
            'name' => 'Cap 5',
            'limits' => ['server_requests_per_day' => 5],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserAiSubscription::query()->create([
            'user_id' => $user->id,
            'ai_subscription_tier_id' => $tier->id,
            'status' => UserAiSubscriptionStatus::Active,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'payment_provider' => 'test',
            'external_payment_ref' => null,
            'cancel_at_period_end' => false,
        ]);

        Sanctum::actingAs($user);
        $this->patchJson('/api/ai/preferences', [
            'max_requests_per_day_self' => 99,
        ])->assertUnprocessable();

        $this->patchJson('/api/ai/preferences', [
            'max_requests_per_day_self' => 3,
        ])->assertOk()
            ->assertJsonPath('data.max_requests_per_day_self', 3);
    }

    public function test_payment_gateway_contract_is_bound(): void
    {
        $gw = app(\App\Contracts\Billing\PaymentGatewayContract::class);
        $this->assertSame('stub', $gw->key());
    }

    public function test_max_ai_chats_blocks_second_ai_conversation(): void
    {
        config(['ai.enabled' => true]);
        $user = User::factory()->create();
        $provider = AiProvider::query()->create([
            'name' => 'OpenAI',
            'driver' => 'openai',
            'config' => [],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $model = AiServerModel::query()->create([
            'ai_provider_id' => $provider->id,
            'vendor_model_id' => 'gpt-4o-mini',
            'display_name' => 'Mini',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'max1chat',
            'name' => 'One AI chat',
            'limits' => ['max_ai_chats' => 1, 'server_requests_per_day' => null],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserAiSubscription::query()->create([
            'user_id' => $user->id,
            'ai_subscription_tier_id' => $tier->id,
            'status' => UserAiSubscriptionStatus::Active,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'payment_provider' => 'test',
            'external_payment_ref' => null,
            'cancel_at_period_end' => false,
        ]);

        Sanctum::actingAs($user);
        $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'First',
            'ai_server_model_id' => $model->id,
        ])->assertCreated();

        $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'Second',
            'ai_server_model_id' => $model->id,
        ])->assertStatus(422);
    }

    public function test_monthly_server_token_quota_blocks(): void
    {
        $user = User::factory()->create();
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'tokcap',
            'name' => 'Token cap',
            'limits' => ['server_tokens_per_month' => 100, 'server_requests_per_day' => null],
            'is_active' => true,
            'sort_order' => 0,
        ]);
        UserAiSubscription::query()->create([
            'user_id' => $user->id,
            'ai_subscription_tier_id' => $tier->id,
            'status' => UserAiSubscriptionStatus::Active,
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'payment_provider' => 'test',
            'external_payment_ref' => null,
            'cancel_at_period_end' => false,
        ]);

        AiUsageLedger::query()->create([
            'user_id' => $user->id,
            'ai_server_model_id' => null,
            'source' => AiRequestSource::Server->value,
            'tokens_prompt' => 40,
            'tokens_completion' => 60,
            'estimated_internal_cost' => null,
            'conversation_id' => null,
            'created_at' => now(),
        ]);

        $svc = app(AiServerQuotaService::class);

        $this->expectException(AiServerQuotaDeniedException::class);
        $svc->assertMayConsumeServerTokensThisMonth($user->fresh());
    }

    public function test_effective_price_after_discount(): void
    {
        $tier = AiSubscriptionTier::query()->create([
            'slug' => 'priced',
            'name' => 'Priced',
            'price_monthly_rub' => 1000,
            'discount_percent' => 10,
            'discount_amount_fixed' => 50,
            'discount_valid_until' => null,
            'limits' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->assertSame('850.00', $tier->fresh()->effectivePriceMonthlyRub());
    }
}
