<?php

namespace App\Services;

use App\Classes\StatClass;
use App\Models\Event;
use App\Services\Analytics\ProductMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DashboardService
{
    public static function dashboard(Request $request)
    {
        $periodDays = (int) $request->integer('days', 30);
        $periodDays = max(1, min(365, $periodDays));

        /** @var ProductMetricsService $metrics */
        $metrics = app(ProductMetricsService::class);
        $snapshot = $metrics->baselineSnapshot($periodDays);

        $matchingCreated = (int) ($snapshot['matching']['search_requests_created'] ?? 0);
        $matchingResponded = (int) ($snapshot['matching']['search_requests_responded'] ?? 0);

        return view('dashboard', [
            'data' => [
                'stat_cards' => StatClass::dashboardStatCards(Auth::user()),
                'baseline_metrics' => [
                    'period_days' => $periodDays,
                    'from' => (string) ($snapshot['from'] ?? ''),
                    'to' => (string) ($snapshot['to'] ?? ''),
                    'matching' => [
                        'search_requests_created' => $matchingCreated,
                        'search_requests_responded' => $matchingResponded,
                        'feed_views' => (int) ($snapshot['matching']['feed_views'] ?? 0),
                        'response_rate' => $matchingCreated > 0
                            ? round(($matchingResponded / $matchingCreated) * 100, 1)
                            : 0.0,
                        'daily' => is_array($snapshot['matching']['daily'] ?? null)
                            ? $snapshot['matching']['daily']
                            : [],
                    ],
                    'integration' => [
                        'v1_calls' => (int) ($snapshot['integration']['v1_calls'] ?? 0),
                        'v2_calls' => (int) ($snapshot['integration']['v2_calls'] ?? 0),
                        'token_minted' => (int) ($snapshot['integration']['token_minted'] ?? 0),
                        'daily' => is_array($snapshot['integration']['daily'] ?? null)
                            ? $snapshot['integration']['daily']
                            : [],
                    ],
                    'ai' => [
                        'support_chat_requests' => (int) ($snapshot['ai']['support_chat_requests'] ?? 0),
                        'moderation_score_requests' => (int) ($snapshot['ai']['moderation_score_requests'] ?? 0),
                        'partner_recommend_requests' => (int) ($snapshot['ai']['partner_recommend_requests'] ?? 0),
                    ],
                    'mobile' => [
                        'sync_manifest_requests' => (int) ($snapshot['mobile']['sync_manifest_requests'] ?? 0),
                        'channels' => is_array($snapshot['mobile']['channels'] ?? null)
                            ? $snapshot['mobile']['channels']
                            : [],
                    ],
                    'observability' => [
                        'notification_delivery_failed' => (int) ($snapshot['observability']['notification_delivery_failed'] ?? 0),
                        'notification_empty_channels' => (int) ($snapshot['observability']['notification_empty_channels'] ?? 0),
                        'queue_job_failed' => (int) ($snapshot['observability']['queue_job_failed'] ?? 0),
                        'slow_api_requests' => (int) ($snapshot['observability']['slow_api_requests'] ?? 0),
                    ],
                    'overview' => [
                        'family_totals' => is_array($snapshot['overview']['family_totals'] ?? null)
                            ? $snapshot['overview']['family_totals']
                            : [],
                        'family_shares' => is_array($snapshot['overview']['family_shares'] ?? null)
                            ? $snapshot['overview']['family_shares']
                            : [],
                        'total_events' => (int) ($snapshot['overview']['total_events'] ?? 0),
                        'top_events' => is_array($snapshot['overview']['top_events'] ?? null)
                            ? $snapshot['overview']['top_events']
                            : [],
                    ],
                ],
            ],
            'buttons' => [],
        ]);
    }

    public static function create_event(Request $request)
    {
        dd('create_event');
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:events,name'],
            'description' => ['required', 'string'],
            'active' => ['sometimes', 'boolean'],
            'resource_id' => ['nullable', 'uuid'],
            'room_id' => ['nullable', 'uuid'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ], [
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Событие с таким названием уже существует.',
            'description.required' => 'Описание обязательно.',
            'end_at.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $data = $validator->validated();

        $event = new Event;
        $event->name = $data['name'];
        $event->description = $data['description'];
        $event->active = $data['active'] ?? true;
        $event->resource_id = $data['resource_id'] ?? null;
        $event->room_id = $data['room_id'] ?? null;
        $event->start_at = $data['start_at'] ?? null;
        $event->end_at = $data['end_at'] ?? null;
        $event->save();

        return ApiService::successResponse('Событие создано', self::formatEvent($event));
    }

    public static function get_event(int $id)
    {
        dd('get_event');
        $event = Event::find($id);

        if (! $event) {
            return ApiService::errorResponse(
                'Событие не найдено.',
                ApiService::EVENT_NOT_FOUND,
                [],
                404
            );
        }

        return ApiService::successResponse('Событие получено', self::formatEvent($event));
    }

    public static function edit_event(int $id, Request $request)
    {
        dd('edit_event');
        $event = Event::find($id);

        if (! $event) {
            return ApiService::errorResponse(
                'Событие не найдено.',
                ApiService::EVENT_NOT_FOUND,
                [],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($event->id)],
            'description' => ['sometimes', 'required', 'string'],
            'active' => ['sometimes', 'boolean'],
            'resource_id' => ['sometimes', 'nullable', 'uuid'],
            'room_id' => ['sometimes', 'nullable', 'uuid'],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_at'],
        ], [
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Событие с таким названием уже существует.',
            'description.required' => 'Описание обязательно.',
            'end_at.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $data = $validator->validated();

        if (array_key_exists('name', $data)) {
            $event->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $event->description = $data['description'];
        }
        if (array_key_exists('active', $data)) {
            $event->active = $data['active'];
        }
        if (array_key_exists('resource_id', $data)) {
            $event->resource_id = $data['resource_id'];
        }
        if (array_key_exists('room_id', $data)) {
            $event->room_id = $data['room_id'];
        }
        if (array_key_exists('start_at', $data)) {
            $event->start_at = $data['start_at'];
        }
        if (array_key_exists('end_at', $data)) {
            $event->end_at = $data['end_at'];
        }

        $event->save();

        return ApiService::successResponse('Событие обновлено', self::formatEvent($event));
    }

    public static function delete_event(int $id)
    {
        dd('delete_event');
        $event = Event::find($id);

        if (! $event) {
            return ApiService::errorResponse(
                'Событие не найдено.',
                ApiService::EVENT_NOT_FOUND,
                [],
                404
            );
        }

        $event->delete();

        return ApiService::successResponse('Событие удалено');
    }

    protected static function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'active' => $event->active,
            'resource_id' => $event->resource_id,
            'room_id' => $event->room_id,
            'start_at' => Carbon::parse($event->start_at)->format('H:i d-m-Y'),
            'end_at' => Carbon::parse($event->end_at)->format('H:i d-m-Y'),
            'created_at' => Carbon::parse($event->created_at)->format('H:i d-m-Y'),
            'updated_at' => Carbon::parse($event->updated_at)->format('H:i d-m-Y'),
        ];
    }
}
