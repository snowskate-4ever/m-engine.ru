<?php

declare(strict_types=1);

namespace App\Services\Agent;

use App\Enums\AiScheduledItemKind;
use App\Enums\AiScheduledItemStatus;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAiScheduledItem;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AgentToolExecutor
{
    public function __construct(
        private readonly AiScheduledItemTimeParser $timeParser,
        private readonly AgentToolInvocationRecorder $invocationRecorder,
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
}
