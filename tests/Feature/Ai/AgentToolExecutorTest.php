<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Enums\AiScheduledItemKind;
use App\Enums\AiScheduledItemStatus;
use App\Enums\ConversationType;
use App\Models\ConcertVenue;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Event;
use App\Models\Peformer;
use App\Models\Resource;
use App\Models\SearchRequest;
use App\Models\Studio;
use App\Models\Type;
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

    public function test_create_music_search_request_tool_checks_ownership(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['venue_representative'],
        ]);
        $other = User::factory()->create();
        $performer = Peformer::query()->create([
            'name' => 'Tool Band',
            'owner_user_id' => $other->id,
            'performer_kind' => 'band',
        ]);
        $ownVenue = ConcertVenue::query()->create([
            'name' => 'Tool Venue',
            'owner_user_id' => $user->id,
        ]);
        $ownStudio = Studio::query()->create([
            'name' => 'Tool Studio',
            'owner_user_id' => $user->id,
        ]);

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
        $failJson = $executor->execute(
            $user,
            $conv,
            'create_music_search_request',
            json_encode([
                'initiator_type' => 'performer',
                'initiator_id' => $performer->id,
                'search_goal' => 'find_organizer_for_performer',
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertFalse((bool) (json_decode($failJson, true)['ok'] ?? true));

        $okJson = $executor->execute(
            $user,
            $conv,
            'create_music_search_request',
            json_encode([
                'search_goal' => 'find_organizer_for_venue',
                'actor_context' => [
                    'type' => ConcertVenue::class,
                    'id' => $ownVenue->id,
                ],
            ], JSON_THROW_ON_ERROR),
        );
        $decoded = json_decode($okJson, true);
        $this->assertTrue($decoded['ok'] ?? false);
        $this->assertDatabaseHas('search_requests', [
            'id' => $decoded['search_request_id'] ?? 0,
            'initiator_type' => ConcertVenue::class,
            'initiator_id' => $ownVenue->id,
        ]);

        $studioJson = $executor->execute(
            $user,
            $conv,
            'create_music_search_request',
            json_encode([
                'initiator_type' => 'studio',
                'initiator_id' => $ownStudio->id,
                'search_goal' => 'find_organizer_for_studio',
            ], JSON_THROW_ON_ERROR),
        );
        $studioDecoded = json_decode($studioJson, true);
        $this->assertTrue($studioDecoded['ok'] ?? false);
        $this->assertDatabaseHas('search_requests', [
            'id' => $studioDecoded['search_request_id'] ?? 0,
            'initiator_type' => Studio::class,
            'initiator_id' => $ownStudio->id,
        ]);
    }

    public function test_confirm_matching_booking_tool_requires_access_and_confirms_booking(): void
    {
        $owner = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);
        $other = User::factory()->create();

        $type = Type::query()->create([
            'name' => 'Tool Resource Type',
            'resource_type' => 'space',
            'description' => 'for tests',
        ]);
        $resource = Resource::query()->create([
            'active' => true,
            'type_id' => $type->id,
            'start_at' => now()->toDateString(),
            'end_at' => now()->addMonth()->toDateString(),
        ]);

        $event = Event::query()->create([
            'name' => 'matching-event-tool-'.$owner->id,
            'description' => 'Event from matching context',
            'active' => true,
            'status' => 'pending',
            'music_organizer_user_id' => $owner->id,
            'matching_proposed_start_at' => now()->addDays(2),
            'matching_proposed_end_at' => now()->addDays(2)->addHours(2),
        ]);

        $conv = Conversation::query()->create([
            'type' => ConversationType::Ai,
            'title' => 'AI',
            'retention_days' => null,
            'created_by_user_id' => $owner->id,
            'ai_server_model_id' => null,
            'user_ai_connection_id' => null,
        ]);
        ConversationUser::query()->create([
            'conversation_id' => $conv->id,
            'user_id' => $owner->id,
            'role' => \App\Enums\ConversationRole::Owner,
        ]);

        $executor = app(AgentToolExecutor::class);

        $failJson = $executor->execute(
            $other,
            $conv,
            'confirm_matching_booking',
            json_encode([
                'event_id' => $event->id,
                'booked_resource_id' => $resource->id,
            ], JSON_THROW_ON_ERROR),
        );
        $this->assertFalse((bool) (json_decode($failJson, true)['ok'] ?? true));

        $okJson = $executor->execute(
            $owner,
            $conv,
            'confirm_matching_booking',
            json_encode([
                'event_id' => $event->id,
                'booked_resource_id' => $resource->id,
            ], JSON_THROW_ON_ERROR),
        );
        $decoded = json_decode($okJson, true);
        $this->assertTrue($decoded['ok'] ?? false);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'booked_resource_id' => $resource->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_list_music_calendar_entries_tool_returns_user_linked_events(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);
        $other = User::factory()->create();
        $linkedEvent = Event::query()->create([
            'name' => 'Linked event',
            'description' => 'd',
            'active' => true,
            'music_organizer_user_id' => $user->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ]);
        Event::query()->create([
            'name' => 'Other event',
            'description' => 'd',
            'active' => true,
            'music_organizer_user_id' => $other->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ]);

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
            'list_music_calendar_entries',
            json_encode([
                'date_from' => now()->toDateString(),
                'date_to' => now()->addDays(2)->toDateString(),
                'event_kind' => 'event',
            ], JSON_THROW_ON_ERROR),
        );

        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['ok'] ?? false);
        $ids = collect($decoded['events'] ?? [])->pluck('id')->all();
        $this->assertSame([$linkedEvent->id], $ids);
    }

    public function test_music_search_tools_and_resource_catalog_return_owned_data(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer'],
        ]);

        $performer = Peformer::query()->create([
            'name' => 'Owned Band',
            'owner_user_id' => $user->id,
            'performer_kind' => 'band',
        ]);
        Studio::query()->create([
            'name' => 'Owned Studio',
            'owner_user_id' => $user->id,
        ]);

        $request = SearchRequest::query()->create([
            'search_goal' => 'find_organizer_for_performer',
            'status' => 'open',
            'initiator_type' => Peformer::class,
            'initiator_id' => $performer->id,
            'created_by_user_id' => $user->id,
            'criteria' => [],
            'submitted_at' => now(),
        ]);

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

        $listJson = $executor->execute(
            $user,
            $conv,
            'list_music_search_requests',
            json_encode(['status' => 'open'], JSON_THROW_ON_ERROR),
        );
        $listDecoded = json_decode($listJson, true);
        $this->assertTrue($listDecoded['ok'] ?? false);
        $this->assertSame($request->id, $listDecoded['items'][0]['id'] ?? null);

        $cancelJson = $executor->execute(
            $user,
            $conv,
            'change_music_search_request_status',
            json_encode(['search_request_id' => $request->id, 'action' => 'cancel'], JSON_THROW_ON_ERROR),
        );
        $cancelDecoded = json_decode($cancelJson, true);
        $this->assertTrue($cancelDecoded['ok'] ?? false);
        $this->assertDatabaseHas('search_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);

        $catalogJson = $executor->execute(
            $user,
            $conv,
            'list_music_resource_catalog',
            json_encode((object) [], JSON_THROW_ON_ERROR),
        );
        $catalogDecoded = json_decode($catalogJson, true);
        $this->assertTrue($catalogDecoded['ok'] ?? false);
        $keys = collect($catalogDecoded['sections'] ?? [])->pluck('key')->all();
        $this->assertContains('performers', $keys);
        $this->assertContains('studios', $keys);
    }
}
