<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EventService
{
    public static function get_events(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'active' => 'sometimes|boolean',
            'resource_id' => 'sometimes|uuid',
            'room_id' => 'sometimes|uuid',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте параметры фильтрации.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $filters = $validator->validated();

        $query = Event::query()->orderByDesc('start_at')->orderByDesc('created_at');

        if (array_key_exists('active', $filters)) {
            $query->where('active', $filters['active']);
        }
        if (isset($filters['resource_id'])) {
            $query->where('resource_id', $filters['resource_id']);
        }
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('start_at', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('end_at', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }

        $events = $query->get()->map(fn (Event $event) => self::formatEvent($event));

        return view('events', [
            'data' => $events,
            'buttons' => ['add']
        ]);
    }

    public static function create_event(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.create_event'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'EventLivewire',
                'buttons' => [],
            ]
        ]);

        // dd('create_event');
        // $validator = Validator::make($request->all(), [
        //     'name' => ['required', 'string', 'max:255', 'unique:events,name'],
        //     'description' => ['required', 'string'],
        //     'active' => ['sometimes', 'boolean'],
        //     'resource_id' => ['nullable', 'uuid'],
        //     'room_id' => ['nullable', 'uuid'],
        //     'start_at' => ['nullable', 'date'],
        //     'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        // ], [
        //     'name.required' => 'Название обязательно.',
        //     'name.unique' => 'Событие с таким названием уже существует.',
        //     'description.required' => 'Описание обязательно.',
        //     'end_at.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
        // ]);

        // if ($validator->fails()) {
        //     return ApiService::errorResponse(
        //         'Проверьте корректность введённых данных.',
        //         ApiService::UNPROCESSABLE_CONTENT,
        //         $validator->errors()->messages(),
        //         422
        //     );
        // }

        // $data = $validator->validated();

        // $event = new Event();
        // $event->name = $data['name'];
        // $event->description = $data['description'];
        // $event->active = $data['active'] ?? true;
        // $event->resource_id = $data['resource_id'] ?? null;
        // $event->room_id = $data['room_id'] ?? null;
        // $event->start_at = $data['start_at'] ?? null;
        // $event->end_at = $data['end_at'] ?? null;
        // $event->save();

        // return ApiService::successResponse('Событие создано', self::formatEvent($event));
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

