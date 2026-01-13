<?php

namespace App\Services\api;

use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiResourceService
{
    public static function get_resources(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'active' => 'sometimes|boolean',
            'type_id' => 'sometimes|integer',
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

        $query = Resource::query()->orderByDesc('created_at');

        if (array_key_exists('active', $filters)) {
            $query->where('active', $filters['active']);
        }
        if (isset($filters['type_id'])) {
            $query->where('type_id', $filters['type_id']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('start_at', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('end_at', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }

        $resources = $query->get()->map(fn (Resource $resource) => self::formatResource($resource));

        return ApiService::successResponse('Список ресурсов получен', ['resources' => $resources]);
    }

    public static function create_resource(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'active' => ['sometimes', 'boolean'],
            'type_id' => ['required', 'integer', 'exists:types,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ], [
            'type_id.required' => 'Тип обязателен.',
            'type_id.exists' => 'Выбранный тип не существует.',
            'start_at.required' => 'Дата начала обязательна.',
            'start_at.date' => 'Дата начала должна быть корректной датой.',
            'end_at.date' => 'Дата окончания должна быть корректной датой.',
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

        $resource = new Resource();
        $resource->active = $data['active'] ?? true;
        $resource->type_id = $data['type_id'];
        $resource->start_at = $data['start_at'];
        $resource->end_at = $data['end_at'] ?? null;
        $resource->save();

        return ApiService::successResponse('Ресурс создан', self::formatResource($resource));
    }

    public static function get_resource(int $id)
    {
        $resource = Resource::find($id);

        if (! $resource) {
            return ApiService::errorResponse(
                'Ресурс не найден.',
                ApiService::RESOURCE_NOT_FOUND,
                [],
                404
            );
        }

        return ApiService::successResponse('Ресурс получен', self::formatResource($resource));
    }

    public static function edit_resource(int $id, Request $request)
    {
        $resource = Resource::find($id);

        if (! $resource) {
            return ApiService::errorResponse(
                'Ресурс не найден.',
                ApiService::RESOURCE_NOT_FOUND,
                [],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'active' => ['sometimes', 'boolean'],
            'type_id' => ['sometimes', 'required', 'integer', 'exists:types,id'],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_at'],
        ], [
            'type_id.required' => 'Тип обязателен.',
            'type_id.exists' => 'Выбранный тип не существует.',
            'start_at.required' => 'Дата начала обязательна.',
            'start_at.date' => 'Дата начала должна быть корректной датой.',
            'end_at.date' => 'Дата окончания должна быть корректной датой.',
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

        if (array_key_exists('active', $data)) {
            $resource->active = $data['active'];
        }
        if (array_key_exists('type_id', $data)) {
            $resource->type_id = $data['type_id'];
        }
        if (array_key_exists('start_at', $data)) {
            $resource->start_at = $data['start_at'];
        }
        if (array_key_exists('end_at', $data)) {
            $resource->end_at = $data['end_at'] ?? null;
        }

        $resource->save();

        return ApiService::successResponse('Ресурс обновлён', self::formatResource($resource));
    }

    public static function delete_resource(int $id)
    {
        $resource = Resource::find($id);

        if (! $resource) {
            return ApiService::errorResponse(
                'Ресурс не найден.',
                ApiService::RESOURCE_NOT_FOUND,
                [],
                404
            );
        }

        $resource->delete();

        return ApiService::successResponse('Ресурс удалён');
    }

    protected static function formatResource(Resource $resource): array
    {
        return [
            'id' => $resource->id,
            'active' => $resource->active,
            'type_id' => $resource->type_id,
            'start_at' => $resource->start_at?->toDateString(),
            'end_at' => $resource->end_at?->toDateString(),
            'created_at' => $resource->created_at?->toISOString(),
            'updated_at' => $resource->updated_at?->toISOString(),
        ];
    }
}

