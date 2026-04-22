<?php

declare(strict_types=1);

namespace Tests\Feature\Messenger;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use Tests\TestCase;

class SupportChatFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_chat_is_auto_created_when_user_lists_conversations(): void
    {
        $user = User::factory()->create();
        config([
            'support_chat.support_user_email' => 'support@m-engine.ru',
            'support_chat.auto_create_user' => true,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/messenger/conversations');
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'direct');
        $support = User::query()->where('email', 'support@m-engine.ru')->first();
        $this->assertNotNull($support);
        $response->assertJsonPath('data.0.direct_peer.id', $support->id);
    }

    public function test_support_chat_page_is_available_for_admin_role(): void
    {
        $support = User::factory()->create(['email' => 'support@m-engine.ru']);
        $customer = User::factory()->create();
        config(['support_chat.support_user_email' => $support->email]);

        Sanctum::actingAs($customer);
        $conversationId = (int) $this->getJson('/api/messenger/conversations')->json('data.0.id');
        $this->postJson("/api/messenger/conversations/{$conversationId}/messages", ['body' => 'Need help'])->assertCreated();

        $adminRole = MoonshineUserRole::query()->create(['name' => 'Admin']);
        $moonAdmin = MoonshineUser::query()->create([
            'moonshine_user_role_id' => $adminRole->id,
            'email' => 'admin@example.test',
            'password' => bcrypt('secret'),
            'name' => 'Admin',
        ]);

        $this->actingAs($moonAdmin, 'moonshine')
            ->get('/admin/support-chats?conversation='.$conversationId)
            ->assertOk()
            ->assertSee('Support чат')
            ->assertSee('Need help');
    }

    public function test_admin_can_generate_draft_and_auto_send_ai_reply(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => 'AI support answer']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 6],
            ], 200),
        ]);

        $support = User::factory()->create(['email' => 'support@m-engine.ru']);
        $customer = User::factory()->create();
        config([
            'support_chat.support_user_email' => $support->email,
            'support_chat.ai.enabled' => true,
            'support_chat.ai.allow_auto_send' => true,
            'ai.openai.server_api_key' => 'sk-test',
            'ai.openai.base_url' => 'https://api.openai.com/v1',
        ]);

        Sanctum::actingAs($customer);
        $conversationId = (int) $this->getJson('/api/messenger/conversations')->json('data.0.id');
        $this->postJson("/api/messenger/conversations/{$conversationId}/messages", ['body' => 'question'])->assertCreated();

        $adminRole = MoonshineUserRole::query()->create(['name' => 'Admin']);
        $moonAdmin = MoonshineUser::query()->create([
            'moonshine_user_role_id' => $adminRole->id,
            'email' => 'admin2@example.test',
            'password' => bcrypt('secret'),
            'name' => 'Admin2',
        ]);

        $this->actingAs($moonAdmin, 'moonshine')
            ->post('/admin/support-chats/'.$conversationId.'/ai-draft')
            ->assertRedirect('/admin/support-chats?conversation='.$conversationId);

        $this->actingAs($moonAdmin, 'moonshine')
            ->post('/admin/support-chats/'.$conversationId.'/ai-send')
            ->assertRedirect('/admin/support-chats?conversation='.$conversationId);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversationId,
            'user_id' => $support->id,
            'body' => '[AI] AI support answer',
        ]);
        $this->assertGreaterThan(0, Message::query()->where('conversation_id', $conversationId)->count());
    }
}
