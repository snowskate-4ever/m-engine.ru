<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Enums\AiScheduledItemKind;
use App\Enums\AiScheduledItemStatus;
use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\User;
use App\Models\UserAiScheduledItem;
use App\Services\Agent\AgentToolExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AgentToolExecutorTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_reminder_creates_row(): void
    {
        $user = User::factory()->create();
        $conv = Conversation::query()->create([
            'type' => ConversationType::Ai,
            'title' => 'AI',
            'retention_days' => null,
            'created_by_user_id' => $user->id,
            'ai_server_model_id' => null,
            'user_ai_connection_id' => null,
        ]);
        ConversationUser::query()->create([
            'conversation_id' => $conv->id,
            'user_id' => $user->id,
            'role' => \App\Enums\ConversationRole::Owner,
        ]);

        $executor = app(AgentToolExecutor::class);
        $json = $executor->execute(
            $user,
            $conv,
            'schedule_reminder',
            json_encode([
                'title' => 'Pay taxes',
                'fire_at' => '2026-12-31T11:00:00',
                'notify_push' => true,
                'notify_email' => false,
            ], JSON_THROW_ON_ERROR),
        );

        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['ok'] ?? false);

        $this->assertDatabaseHas('user_ai_scheduled_items', [
            'user_id' => $user->id,
            'conversation_id' => $conv->id,
            'kind' => AiScheduledItemKind::TaskReminder->value,
            'title' => 'Pay taxes',
            'status' => AiScheduledItemStatus::Pending->value,
        ]);
    }

    public function test_list_scheduled_items_api(): void
    {
        $user = User::factory()->create();
        UserAiScheduledItem::query()->create([
            'user_id' => $user->id,
            'conversation_id' => null,
            'kind' => AiScheduledItemKind::Custom,
            'title' => 'T',
            'payload' => null,
            'next_fire_at' => now()->addDay(),
            'repeat_rule' => null,
            'notify_push' => true,
            'notify_email' => false,
            'status' => AiScheduledItemStatus::Pending,
        ]);

        Sanctum::actingAs($user);
        $this->getJson('/api/ai/scheduled-items')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'T');
    }
}
