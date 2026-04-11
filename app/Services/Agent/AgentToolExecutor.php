<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Enums\AiScheduledItemKind;
use App\Enums\AiScheduledItemStatus;
use App\Enums\SearchGoal;
use App\Models\ConcertVenue;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAiScheduledItem;
use App\Services\BookingService;
use App\Services\Music\MusicCalendarFeedService;
use App\Services\Music\SearchRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AgentToolExecutor
{
    public function __construct(
        private readonly AiScheduledItemTimeParser $timeParser,
        private readonly AgentToolInvocationRecorder $invocationRecorder,
        private readonly SearchRequestService $searchRequestService,
        private readonly MusicCalendarFeedService $calendarFeedService,
    ) {}

    public function execute(User $user, Conversation $conversation, string $name, string $argumentsJson): string
    {
        $hash = substr(hash('sha256', $argumentsJson), 0, 16);

        try {
            /** @var array<string, mixed> $args */
            $args = json_decode($argumentsJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->invocationRecorder->record(
                $user->id,
                $conversation->id,
                $name,
                $hash,
                false,
                'Invalid JSON arguments: '.$e->getMessage(),
            );

            return json_encode(['ok' => false, 'error' => 'Invalid JSON arguments: '.$e->getMessage()], JSON_UNESCAPED_UNICODE) ?: '{"ok":false}';
        }

        $ok = false;
        $error = null;
        $payload = null;

        try {
            $result = match ($name) {
                'schedule_reminder' => $this->scheduleReminder($user, $conversation, $args),
                'create_task_with_deadline' => $this->createTaskWithDeadline($user, $args),
                'link_event_booking_reminder' => $this->linkEventBookingReminder($user, $conversation, $args),
                'list_music_calendar_entries' => $this->listMusicCalendarEntries($user, $args),
                'create_music_search_request' => $this->createMusicSearchRequest($user, $args),
                'confirm_matching_booking' => $this->confirmMatchingBooking($user, $args),
                default => ['ok' => false, 'error' => 'Unknown tool: '.$name],
            };
            $ok = (bool) ($result['ok'] ?? false);
            if (! $ok) {
                $err = $result['error'] ?? null;
                $error = is_string($err) && $err !== '' ? $err : 'Tool failed';
            }
            $payload = json_encode($result, JSON_UNESCAPED_UNICODE) ?: '{"ok":false}';
        } catch (Throwable $e) {
            Log::warning('agent.tool_failed', [
                'user_id' => $user->id,
                'tool' => $name,
                'message' => $e->getMessage(),
            ]);
            $error = $e->getMessage();
            $payload = json_encode(['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE) ?: '{"ok":false}';
        }

        Log::info('agent.tool_call', [
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'tool' => $name,
            'args_hash' => $hash,
            'ok' => $ok,
        ]);

        $this->invocationRecorder->record(
            $user->id,
            $conversation->id,
            $name,
            $hash,
            $ok,
            $error,
        );

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function scheduleReminder(User $user, Conversation $conversation, array $args): array
    {
        $title = isset($args['title']) && is_string($args['title']) ? trim($args['title']) : '';
        $fireAt = isset($args['fire_at']) && is_string($args['fire_at']) ? trim($args['fire_at']) : '';
        if ($title === '' || $fireAt === '') {
            return ['ok' => false, 'error' => 'title and fire_at are required'];
        }

        $next = $this->timeParser->parseToUtcCarbon($fireAt);
        $repeat = isset($args['repeat_rule']) && is_string($args['repeat_rule']) ? trim($args['repeat_rule']) : null;
        if ($repeat === '') {
            $repeat = null;
        }

        $notifyPush = ! isset($args['notify_push']) || $args['notify_push'] !== false;
        $notifyEmail = isset($args['notify_email']) && $args['notify_email'] === true;

        $payload = [
            'rrule_anchor_utc' => $next->clone()->utc()->toIso8601String(),
        ];

        $item = UserAiScheduledItem::query()->create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'kind' => AiScheduledItemKind::TaskReminder,
            'title' => $title,
            'payload' => $payload,
            'next_fire_at' => $next,
            'repeat_rule' => $repeat,
            'notify_push' => $notifyPush,
            'notify_email' => $notifyEmail,
            'status' => \App\Enums\AiScheduledItemStatus::Pending,
        ]);

        return ['ok' => true, 'scheduled_item_id' => $item->id, 'next_fire_at' => $item->next_fire_at->toIso8601String()];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function createTaskWithDeadline(User $user, array $args): array
    {
        $name = isset($args['name']) && is_string($args['name']) ? trim($args['name']) : '';
        $deadlineAt = isset($args['deadline_at']) && is_string($args['deadline_at']) ? trim($args['deadline_at']) : '';
        if ($name === '' || $deadlineAt === '') {
            return ['ok' => false, 'error' => 'name and deadline_at are required'];
        }

        $deadlineUtc = $this->timeParser->parseToUtcCarbon($deadlineAt);
        $desc = isset($args['description']) && is_string($args['description']) ? $args['description'] : '';
        $deadlineLine = 'Срок (UTC): '.$deadlineUtc->toIso8601String();
        $fullDesc = $desc !== '' ? $desc."\n\n".$deadlineLine : $deadlineLine;

        $task = Task::query()->create([
            'name' => $name,
            'description' => $fullDesc,
            'status' => 'planned',
            'user_id' => $user->id,
        ]);

        return ['ok' => true, 'task_id' => $task->id];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function linkEventBookingReminder(User $user, Conversation $conversation, array $args): array
    {
        $eventId = isset($args['event_id']) ? (int) $args['event_id'] : 0;
        $remindAt = isset($args['remind_at']) && is_string($args['remind_at']) ? trim($args['remind_at']) : '';
        if ($eventId < 1 || $remindAt === '') {
            return ['ok' => false, 'error' => 'event_id and remind_at are required'];
        }

        $event = Event::query()->whereKey($eventId)->where('user_id', $user->id)->first();
        if ($event === null) {
            return ['ok' => false, 'error' => 'Event not found or not owned by user'];
        }

        $next = $this->timeParser->parseToUtcCarbon($remindAt);
        $title = 'Напоминание: '.(is_string($event->name) ? $event->name : 'событие #'.$event->id);

        $item = UserAiScheduledItem::query()->create([
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'kind' => AiScheduledItemKind::EventBooking,
            'title' => $title,
            'payload' => [
                'event_id' => $event->id,
                'rrule_anchor_utc' => $next->clone()->utc()->toIso8601String(),
            ],
            'next_fire_at' => $next,
            'repeat_rule' => null,
            'notify_push' => true,
            'notify_email' => false,
            'status' => AiScheduledItemStatus::Pending,
        ]);

        return ['ok' => true, 'scheduled_item_id' => $item->id];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function createMusicSearchRequest(User $user, array $args): array
    {
        $initiatorType = isset($args['initiator_type']) && is_string($args['initiator_type']) ? trim($args['initiator_type']) : '';
        $initiatorId = isset($args['initiator_id']) ? (int) $args['initiator_id'] : 0;
        $searchGoalRaw = isset($args['search_goal']) && is_string($args['search_goal']) ? trim($args['search_goal']) : '';
        $criteria = isset($args['criteria']) && is_array($args['criteria']) ? $args['criteria'] : [];
        $actorContext = isset($args['actor_context']) && is_array($args['actor_context']) ? $args['actor_context'] : [];

        if ($searchGoalRaw === '') {
            return ['ok' => false, 'error' => 'search_goal is required'];
        }

        $goal = SearchGoal::tryFrom($searchGoalRaw);
        if ($goal === null) {
            return ['ok' => false, 'error' => 'Unknown search_goal'];
        }

        $contextType = isset($actorContext['type']) && is_string($actorContext['type']) ? trim($actorContext['type']) : null;
        $contextId = isset($actorContext['id']) ? (int) $actorContext['id'] : null;

        if ($contextType === null && $initiatorType !== '' && $initiatorId > 0) {
            [$contextType, $contextId] = match ($initiatorType) {
                'performer' => [Peformer::class, $initiatorId],
                'concert_venue' => [ConcertVenue::class, $initiatorId],
                'studio' => [Studio::class, $initiatorId],
                'rehearsal' => [Rehersal::class, $initiatorId],
                'school' => [School::class, $initiatorId],
                default => [null, null],
            };
        }

        try {
            $request = $this->searchRequestService->createUsingActorContext(
                $user,
                $goal,
                $criteria,
                $contextType,
                $contextId,
            );
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return [
            'ok' => true,
            'search_request_id' => $request->id,
            'status' => $request->status?->value ?? (string) $request->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function confirmMatchingBooking(User $user, array $args): array
    {
        $eventId = isset($args['event_id']) ? (int) $args['event_id'] : 0;
        $bookedResourceId = isset($args['booked_resource_id']) ? (int) $args['booked_resource_id'] : 0;
        $roomId = isset($args['room_id']) ? (int) $args['room_id'] : null;
        $bookingResourceId = isset($args['booking_resource_id']) ? (int) $args['booking_resource_id'] : null;

        if ($eventId < 1 || $bookedResourceId < 1) {
            return ['ok' => false, 'error' => 'event_id and booked_resource_id are required'];
        }

        $event = Event::query()->find($eventId);
        if ($event === null) {
            return ['ok' => false, 'error' => 'Event not found'];
        }

        $isOwner = (int) ($event->user_id ?? 0) === (int) $user->id;
        $isOrganizer = (int) ($event->music_organizer_user_id ?? 0) === (int) $user->id;
        if (! $isOwner && ! $isOrganizer) {
            return ['ok' => false, 'error' => 'Event not found or not accessible by user'];
        }

        try {
            $confirmed = app(BookingService::class)->confirmMatchingBooking(
                $event->id,
                $bookedResourceId,
                $roomId,
                $bookingResourceId,
            );
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return [
            'ok' => true,
            'event_id' => $confirmed->id,
            'booked_resource_id' => $confirmed->booked_resource_id,
            'room_id' => $confirmed->room_id,
            'status' => (string) $confirmed->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function listMusicCalendarEntries(User $user, array $args): array
    {
        $tz = (string) config('app.timezone');
        $dateFrom = isset($args['date_from']) && is_string($args['date_from']) ? trim($args['date_from']) : '';
        $dateTo = isset($args['date_to']) && is_string($args['date_to']) ? trim($args['date_to']) : '';
        $eventKind = isset($args['event_kind']) && is_string($args['event_kind']) ? trim($args['event_kind']) : MusicCalendarFeedService::EVENT_KIND_ALL;
        $ownerEntityType = isset($args['owner_entity_type']) && is_string($args['owner_entity_type']) ? trim($args['owner_entity_type']) : null;
        $ownerEntityId = isset($args['owner_entity_id']) ? (int) $args['owner_entity_id'] : null;

        $startUtc = $dateFrom !== ''
            ? CarbonImmutable::parse($dateFrom, $tz)->startOfDay()->utc()
            : CarbonImmutable::now($tz)->startOfMonth()->startOfDay()->utc();
        $endUtc = $dateTo !== ''
            ? CarbonImmutable::parse($dateTo, $tz)->endOfDay()->utc()
            : CarbonImmutable::now($tz)->endOfMonth()->endOfDay()->utc();

        $ownerEntity = null;
        if ($ownerEntityType !== null && $ownerEntityId !== null && $ownerEntityId > 0) {
            $ownerEntity = $ownerEntityType.'#'.$ownerEntityId;
        }

        $events = $this->calendarFeedService
            ->eventsForRange($user, $startUtc, $endUtc, [
                'event_kind' => $eventKind,
                'owner_entity' => $ownerEntity ?? 'all_linked',
                'include_related_entities' => true,
            ])
            ->take(100)
            ->map(static fn (Event $event): array => [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
                'is_booking' => $event->isBooking(),
                'start_at' => $event->start_at?->toIso8601String(),
                'end_at' => $event->end_at?->toIso8601String(),
                'booked_resource_id' => $event->booked_resource_id,
                'room_id' => $event->room_id,
            ])
            ->values()
            ->all();

        return [
            'ok' => true,
            'count' => count($events),
            'events' => $events,
        ];
    }
}
