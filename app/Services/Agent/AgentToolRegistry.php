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
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_music_calendar_entries',
                    'description' => 'List music events and bookings from the calendar with filters by date range, entry kind, and owner entity.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'date_from' => ['type' => 'string', 'description' => 'Date in YYYY-MM-DD format'],
                            'date_to' => ['type' => 'string', 'description' => 'Date in YYYY-MM-DD format'],
                            'event_kind' => ['type' => 'string', 'description' => 'all, event, booking, room_booking, resource_booking'],
                            'owner_entity_type' => ['type' => 'string', 'description' => 'Owner type alias or model class'],
                            'owner_entity_id' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'create_music_search_request',
                    'description' => 'Create a music matching search request for an owned performer or space profile (concert venue, studio, rehearsal, school).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'initiator_type' => [
                                'type' => 'string',
                                'description' => 'Allowed values: performer, concert_venue, studio, rehearsal, school',
                            ],
                            'initiator_id' => ['type' => 'integer'],
                            'search_goal' => [
                                'type' => 'string',
                                'description' => 'Matching goal code, e.g. find_organizer_for_performer',
                            ],
                            'actor_context' => [
                                'type' => 'object',
                                'description' => 'Optional actor context override. If omitted, current active actor is used.',
                                'properties' => [
                                    'type' => ['type' => 'string', 'description' => 'Model FQCN like App\\\\Models\\\\Peformer'],
                                    'id' => ['type' => 'integer'],
                                ],
                            ],
                            'criteria' => [
                                'type' => 'object',
                                'description' => 'Optional filters for candidate selection',
                            ],
                        ],
                        'required' => ['initiator_type', 'initiator_id', 'search_goal'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_music_search_requests',
                    'description' => 'List current user search requests in the music matching workflow with latest status and initiator labels.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'description' => 'Optional status filter, e.g. open, cancelled, fulfilled',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Max number of requests to return (1..100). Default 25.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'change_music_search_request_status',
                    'description' => 'Cancel an open search request or reopen a cancelled/expired/failed request owned by the current user.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'search_request_id' => ['type' => 'integer'],
                            'action' => [
                                'type' => 'string',
                                'description' => 'Allowed values: cancel, reopen',
                            ],
                        ],
                        'required' => ['search_request_id', 'action'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'list_music_resource_catalog',
                    'description' => 'List all owned music resource entities grouped by category (performers, studios, rehearsal spaces, venues, schools, labels, producer centers, shops).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'confirm_matching_booking',
                    'description' => 'Confirm booking for an existing event using its matching slot context and selected resource/room.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'event_id' => ['type' => 'integer'],
                            'booked_resource_id' => ['type' => 'integer'],
                            'room_id' => ['type' => 'integer'],
                            'booking_resource_id' => ['type' => 'integer'],
                        ],
                        'required' => ['event_id', 'booked_resource_id'],
                    ],
                ],
            ],
        ];
    }
}
