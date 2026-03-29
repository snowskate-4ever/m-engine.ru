<?php

declare(strict_types=1);

namespace App\Services\Agent;

/**
 * OpenAI-compatible tool definitions (§14 roadmap).
 *
 * @phpstan-type ToolDef array{type: 'function', function: array{name: string, description: string, parameters: array<string, mixed>}}
 */
final class AgentToolRegistry
{
    /**
     * @return list<ToolDef>
     */
    public function definitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'schedule_reminder',
                    'description' => 'Schedule a one-time or recurring reminder for the user. '
                        .'Datetime `fire_at` without timezone is interpreted as Europe/Moscow wall time.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Short reminder title'],
                            'fire_at' => ['type' => 'string', 'description' => 'ISO 8601 datetime (e.g. 2026-04-01T11:00:00 or with Z)'],
                            'repeat_rule' => [
                                'type' => 'string',
                                'description' => 'Optional RFC5545 RRULE part only, e.g. FREQ=DAILY;INTERVAL=1 or FREQ=WEEKLY;BYDAY=MO',
                            ],
                            'notify_push' => ['type' => 'boolean', 'description' => 'Send mobile push when due', 'default' => true],
                            'notify_email' => ['type' => 'boolean', 'description' => 'Send email when due (verified email only)', 'default' => false],
                        ],
                        'required' => ['title', 'fire_at'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_task_with_deadline',
                    'description' => 'Create a task for the user. Deadline is appended to the description if the schema has no due field.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string', 'description' => 'Optional details'],
                            'deadline_at' => ['type' => 'string', 'description' => 'ISO datetime; Europe/Moscow if no offset'],
                        ],
                        'required' => ['name', 'deadline_at'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'link_event_booking_reminder',
                    'description' => 'Schedule a reminder related to an existing event/booking owned by the user.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'event_id' => ['type' => 'integer'],
                            'remind_at' => ['type' => 'string', 'description' => 'When to remind; Europe/Moscow if no timezone'],
                        ],
                        'required' => ['event_id', 'remind_at'],
                    ],
                ],
            ],
        ];
    }
}
