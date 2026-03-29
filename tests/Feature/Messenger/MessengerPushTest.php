<?php

declare(strict_types=1);

namespace Tests\Feature\Messenger;

use App\Jobs\SendMessengerPushNotificationsJob;
use App\Models\ConversationUser;
use App\Models\MessengerUserPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessengerPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_push_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/devices/push-token', [
            'token' => 'fcm-token-abc',
            'platform' => 'android',
            'app_version' => '1.0.0',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('device_push_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-abc',
            'platform' => 'android',
            'app_version' => '1.0.0',
        ]);
    }

    public function test_same_token_moves_to_new_user(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $this->postJson('/api/devices/push-token', [
            'token' => 'shared-device',
            'platform' => 'ios',
        ])->assertOk();

        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => 'shared-device',
            'platform' => 'ios',
        ])->assertOk();

        $this->assertDatabaseMissing('device_push_tokens', ['user_id' => $a->id]);
        $this->assertDatabaseHas('device_push_tokens', [
            'user_id' => $b->id,
            'token' => 'shared-device',
        ]);
    }

    public function test_message_dispatches_push_job(): void
    {
        Queue::fake([SendMessengerPushNotificationsJob::class]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'ping',
        ])->assertStatus(201);

        Queue::assertPushed(SendMessengerPushNotificationsJob::class, function (SendMessengerPushNotificationsJob $job) use ($cid) {
            $message = \App\Models\Message::query()->where('conversation_id', $cid)->first();

            return $message !== null && $job->messageId === $message->id;
        });
    }

    public function test_fcm_request_sent_when_configured(): void
    {
        config(['messenger.fcm.legacy_server_key' => 'test-server-key']);
        Http::fake([
            'fcm.googleapis.com/*' => Http::response([
                'success' => 1,
                'failure' => 0,
                'results' => [[]],
            ], 200),
        ]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => 'device-1',
            'platform' => 'android',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'hello push',
        ])->assertStatus(201);

        Http::assertSentCount(1);
    }

    public function test_no_fcm_when_recipient_muted_chat(): void
    {
        config(['messenger.fcm.legacy_server_key' => 'test-server-key']);
        Http::fake();

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => 'device-muted',
            'platform' => 'android',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        ConversationUser::query()
            ->where('conversation_id', $cid)
            ->where('user_id', $b->id)
            ->update(['notifications_muted' => true]);

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'muted',
        ])->assertStatus(201);

        Http::assertNothingSent();
    }

    public function test_no_fcm_when_global_push_disabled(): void
    {
        config(['messenger.fcm.legacy_server_key' => 'test-server-key']);
        Http::fake();

        $a = User::factory()->create();
        $b = User::factory()->create();

        MessengerUserPreference::query()->create([
            'user_id' => $b->id,
            'push_enabled' => false,
        ]);

        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => 'device-off',
            'platform' => 'android',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'prefs off',
        ])->assertStatus(201);

        Http::assertNothingSent();
    }

    public function test_invalid_fcm_token_removed(): void
    {
        config(['messenger.fcm.legacy_server_key' => 'test-server-key']);
        Http::fake([
            'fcm.googleapis.com/*' => Http::response([
                'success' => 0,
                'failure' => 1,
                'results' => [['error' => 'NotRegistered']],
            ], 200),
        ]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => 'dead-token',
            'platform' => 'android',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'x',
        ])->assertStatus(201);

        $this->assertDatabaseMissing('device_push_tokens', ['token' => 'dead-token']);
    }

    public function test_apns_request_sent_when_configured(): void
    {
        $p8 = base_path('tests/fixtures/apns_test_auth_key.p8');
        $this->assertFileExists($p8);
        config([
            'messenger.apns.team_id' => 'ABCDE12345',
            'messenger.apns.key_id' => 'DUMMYKEYID',
            'messenger.apns.bundle_id' => 'ru.test.app',
            'messenger.apns.auth_key_path' => $p8,
            'messenger.apns.use_sandbox' => true,
        ]);
        Http::fake([
            'api.sandbox.push.apple.com/*' => Http::response('', 200),
        ]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => str_repeat('b', 64),
            'platform' => 'ios',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'hello apns',
        ])->assertStatus(201);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $url = (string) $request->url();
            $headers = $request->headers();
            $auth = $headers['authorization'] ?? [];

            return str_contains($url, 'api.sandbox.push.apple.com/3/device/')
                && str_contains($url, str_repeat('b', 64))
                && isset($auth[0])
                && str_starts_with(strtolower((string) $auth[0]), 'bearer ');
        });
    }

    public function test_no_apns_request_when_only_android_token_and_apns_configured(): void
    {
        $p8 = base_path('tests/fixtures/apns_test_auth_key.p8');
        config([
            'messenger.apns.team_id' => 'ABCDE12345',
            'messenger.apns.key_id' => 'DUMMYKEYID',
            'messenger.apns.bundle_id' => 'ru.test.app',
            'messenger.apns.auth_key_path' => $p8,
            'messenger.apns.use_sandbox' => true,
        ]);
        Http::fake();

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($b);
        $this->postJson('/api/devices/push-token', [
            'token' => 'android-only-fcm',
            'platform' => 'android',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'no transport',
        ])->assertStatus(201);

        Http::assertNothingSent();
    }

    public function test_invalid_apns_token_removed(): void
    {
        $p8 = base_path('tests/fixtures/apns_test_auth_key.p8');
        config([
            'messenger.apns.team_id' => 'ABCDE12345',
            'messenger.apns.key_id' => 'DUMMYKEYID',
            'messenger.apns.bundle_id' => 'ru.test.app',
            'messenger.apns.auth_key_path' => $p8,
            'messenger.apns.use_sandbox' => true,
        ]);
        Http::fake([
            'api.sandbox.push.apple.com/*' => Http::response(
                json_encode(['reason' => 'BadDeviceToken'], JSON_THROW_ON_ERROR),
                400,
            ),
        ]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($b);
        $hexToken = str_repeat('c', 64);
        $this->postJson('/api/devices/push-token', [
            'token' => $hexToken,
            'platform' => 'ios',
        ])->assertOk();

        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'x',
        ])->assertStatus(201);

        $this->assertDatabaseMissing('device_push_tokens', ['token' => $hexToken]);
    }
}
