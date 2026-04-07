<?php

declare(strict_types=1);

namespace Tests\Feature\Messenger;

use App\Enums\ConversationRole;
use App\Events\Messenger\ConversationRetentionUpdated;
use App\Models\AiProvider;
use App\Models\AiServerModel;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessengerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_direct_conversation_and_lists_it(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);

        $r = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ]);

        $r->assertStatus(201)
            ->assertJsonPath('data.type', 'direct')
            ->assertJsonPath('data.direct_peer.id', $b->id);

        $this->assertDatabaseHas('conversations', [
            'type' => 'direct',
            'direct_peer_min_id' => min($a->id, $b->id),
            'direct_peer_max_id' => max($a->id, $b->id),
        ]);

        $list = $this->getJson('/api/messenger/conversations');
        $list->assertOk();
        $this->assertCount(1, $list->json('data'));
    }

    public function test_direct_conversation_is_reused_for_same_pair(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);

        $id1 = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $id2 = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->assertSame($id1, $id2);
        $this->assertSame(1, Conversation::query()->count());
    }

    public function test_ai_conversation_requires_exactly_one_model_or_connection(): void
    {
        config(['ai.enabled' => true]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'Bot',
        ])->assertStatus(422);
    }

    public function test_ai_conversation_rejected_when_master_disabled(): void
    {
        config(['ai.enabled' => false]);
        Sanctum::actingAs(User::factory()->create());
        $p = AiProvider::query()->create([
            'name' => 'OpenAI',
            'driver' => 'openai',
            'config' => ['api_key' => 'sk-test'],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $m = AiServerModel::query()->create([
            'ai_provider_id' => $p->id,
            'vendor_model_id' => 'gpt-4o-mini',
            'display_name' => 'Mini',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'Bot',
            'ai_server_model_id' => $m->id,
        ])->assertStatus(422);
    }

    public function test_ai_server_chat_calls_openai_and_stores_assistant_message(): void
    {
        config(['ai.enabled' => true]);
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Hello from AI']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
            ], 200),
        ]);

        $u = User::factory()->create();
        $p = AiProvider::query()->create([
            'name' => 'OpenAI',
            'driver' => 'openai',
            'config' => ['api_key' => 'sk-test'],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $m = AiServerModel::query()->create([
            'ai_provider_id' => $p->id,
            'vendor_model_id' => 'gpt-4o-mini',
            'display_name' => 'Mini',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($u);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'My bot',
            'ai_server_model_id' => $m->id,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'Hi',
        ])->assertCreated();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $cid,
            'user_id' => null,
            'body' => 'Hello from AI',
        ]);
        $this->assertDatabaseCount('ai_request_logs', 1);
    }

    public function test_server_ai_second_message_blocked_when_daily_quota_exhausted(): void
    {
        config(['ai.enabled' => true]);
        config(['billing.trial_max_requests_per_day' => 1]);
        config(['billing.trial_duration_days' => 14]);
        config(['billing.unpaid_daily_server_request_allowance' => 0]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'Once']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $u = User::factory()->create();
        $p = AiProvider::query()->create([
            'name' => 'OpenAI',
            'driver' => 'openai',
            'config' => ['api_key' => 'sk-test'],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $m = AiServerModel::query()->create([
            'ai_provider_id' => $p->id,
            'vendor_model_id' => 'gpt-4o-mini',
            'display_name' => 'Mini',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($u);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'Bot',
            'ai_server_model_id' => $m->id,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", ['body' => 'first'])->assertCreated();
        $this->postJson("/api/messenger/conversations/{$cid}/messages", ['body' => 'second'])
            ->assertStatus(402)
            ->assertJsonPath('code', 'quota_exceeded');

        Http::assertSentCount(1);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $cid,
            'kind' => 'system',
            'body' => __('ui.messenger.ai_quota_exceeded'),
        ]);
    }

    public function test_server_ai_paid_subscription_bypasses_daily_quota(): void
    {
        config(['ai.enabled' => true]);
        config(['billing.trial_max_requests_per_day' => 1]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'R']]],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
            ], 200),
        ]);

        $u = User::factory()->create();
        $u->forceFill(['ai_subscription_valid_until' => now()->addYear()])->save();

        $p = AiProvider::query()->create([
            'name' => 'OpenAI',
            'driver' => 'openai',
            'config' => ['api_key' => 'sk-test'],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $m = AiServerModel::query()->create([
            'ai_provider_id' => $p->id,
            'vendor_model_id' => 'gpt-4o-mini',
            'display_name' => 'Mini',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($u);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'Bot',
            'ai_server_model_id' => $m->id,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", ['body' => 'a'])->assertCreated();
        $this->postJson("/api/messenger/conversations/{$cid}/messages", ['body' => 'b'])->assertCreated();

        Http::assertSentCount(2);
    }

    public function test_openai_compatible_provider_calls_configured_base_url(): void
    {
        config(['ai.enabled' => true]);
        Http::fake([
            'https://api.example.test/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'From compatible API']]],
                'usage' => ['prompt_tokens' => 2, 'completion_tokens' => 3],
            ], 200),
        ]);

        $u = User::factory()->create();
        $p = AiProvider::query()->create([
            'name' => 'Test compatible',
            'driver' => 'openai_compatible',
            'config' => [
                'api_key' => 'sk-compat',
                'base_url' => 'https://api.example.test/v1',
            ],
            'scope' => 'server',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $m = AiServerModel::query()->create([
            'ai_provider_id' => $p->id,
            'vendor_model_id' => 'test-model',
            'display_name' => 'Test',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($u);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'ai',
            'title' => 'Compat',
            'ai_server_model_id' => $m->id,
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'Hi',
        ])->assertCreated();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $cid,
            'user_id' => null,
            'body' => 'From compatible API',
        ]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return str_starts_with((string) $request->url(), 'https://api.example.test/v1/chat/completions');
        });
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        Sanctum::actingAs($c);
        $this->getJson("/api/messenger/conversations/{$cid}")->assertForbidden();
    }

    public function test_send_message_and_idempotent_client_message_id(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $r1 = $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'hello',
            'client_message_id' => $uuid,
        ]);
        $r1->assertStatus(201);
        $mid = $r1->json('data.id');

        $r2 = $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'retry',
            'client_message_id' => $uuid,
        ]);
        $r2->assertStatus(201)->assertJsonPath('data.id', $mid);
        $this->assertSame(1, Message::query()->where('conversation_id', $cid)->count());
    }

    public function test_group_owner_can_set_retention_member_forbidden(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        Sanctum::actingAs($owner);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'group',
            'title' => 'Team',
            'user_ids' => [$member->id],
        ])->json('data.id');

        $this->patchJson("/api/messenger/conversations/{$cid}", [
            'retention_days' => 30,
        ])->assertOk();

        Sanctum::actingAs($member);
        $this->patchJson("/api/messenger/conversations/{$cid}", [
            'retention_days' => 14,
        ])->assertForbidden();
    }

    public function test_direct_retention_change_dispatches_event_for_peer(): void
    {
        Event::fake([ConversationRetentionUpdated::class]);
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $this->patchJson("/api/messenger/conversations/{$cid}", [
            'retention_days' => 7,
        ])->assertOk();

        Event::assertDispatched(ConversationRetentionUpdated::class, function (ConversationRetentionUpdated $e) use ($b, $a) {
            return $e->notifyUserId === $b->id
                && $e->changedBy->is($a)
                && $e->retentionDays === 7;
        });
    }

    public function test_forward_message_between_chats(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();
        Sanctum::actingAs($a);
        $c1 = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');
        $c2 = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $c->id,
        ])->json('data.id');

        $mid = $this->postJson("/api/messenger/conversations/{$c1}/messages", [
            'body' => 'secret',
        ])->json('data.id');

        Sanctum::actingAs($a);
        $r = $this->postJson("/api/messenger/conversations/{$c2}/messages", [
            'forward_from_message_id' => $mid,
        ]);
        $r->assertStatus(201)
            ->assertJsonPath('data.is_forward', true)
            ->assertJsonPath('data.forward_snapshot.body', 'secret');
    }

    public function test_messenger_preferences_roundtrip(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);
        $this->getJson('/api/messenger/preferences')->assertOk()
            ->assertJsonPath('data.push_enabled', true);

        $this->patchJson('/api/messenger/preferences', [
            'push_enabled' => false,
        ])->assertOk()->assertJsonPath('data.push_enabled', false);
    }

    public function test_mark_read_updates_pivot(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');
        $mid = $this->postJson("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'x',
        ])->json('data.id');

        $this->postJson("/api/messenger/conversations/{$cid}/read", [
            'message_id' => $mid,
        ])->assertOk();

        $lr = ConversationUser::query()
            ->where('conversation_id', $cid)
            ->where('user_id', $a->id)
            ->value('last_read_message_id');
        $this->assertSame($mid, $lr);
    }

    public function test_group_member_role_on_create(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        Sanctum::actingAs($owner);
        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'group',
            'title' => 'G',
            'user_ids' => [$member->id],
        ])->json('data.id');

        $this->assertTrue(
            ConversationUser::query()->where('conversation_id', $cid)->where('user_id', $owner->id)->first()->role === ConversationRole::Owner,
        );
        $this->assertTrue(
            ConversationUser::query()->where('conversation_id', $cid)->where('user_id', $member->id)->first()->role === ConversationRole::Member,
        );
    }

    public function test_attachment_signed_download_url_streams_file(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);

        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $upload = $this->post("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'see file',
            'attachments' => [
                UploadedFile::fake()->create('report.pdf', 50, 'application/pdf'),
            ],
        ]);

        $upload->assertCreated();
        $url = $upload->json('data.attachments.0.download_url');
        $this->assertIsString($url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);

        $this->get($url)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $bad = preg_replace('/signature=[^&]+/', 'signature=invalid', (string) $url);
        $this->get($bad)->assertForbidden();
    }

    public function test_signed_attachment_download_expires_after_ttl(): void
    {
        Storage::fake(config('filesystems.default', 'local'));
        config(['messenger.attachment_download_ttl_minutes' => 1]);

        $a = User::factory()->create();
        $b = User::factory()->create();
        Sanctum::actingAs($a);

        $cid = $this->postJson('/api/messenger/conversations', [
            'type' => 'direct',
            'user_id' => $b->id,
        ])->json('data.id');

        $url = $this->post("/api/messenger/conversations/{$cid}/messages", [
            'body' => 'f',
            'attachments' => [UploadedFile::fake()->create('a.txt', 20)],
        ])->json('data.attachments.0.download_url');

        $this->travel(2)->minutes();
        $this->get($url)->assertForbidden();
    }
}
