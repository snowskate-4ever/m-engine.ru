<?php

namespace App\Services;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiTypeService
{
    public static function get_types(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'resource_type' => 'sometimes|string',
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

        $query = Type::query()->orderBy('name');

        if (isset($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }

        $types = $query->get()->map(fn (Type $type) => self::formatType($type));

        return ApiService::successResponse('Список типов получен', ['types' => $types]);
    }

    public static function create_type(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:types,name'],
            'resource_type' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ], [
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Тип с таким названием уже существует.',
            'description.required' => 'Описание обязательно.',
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

        $type = new Type();
        $type->name = $data['name'];
        $type->resource_type = $data['resource_type'] ?? null;
        $type->description = $data['description'];
        $type->save();

        return ApiService::successResponse('Тип создан', self::formatType($type));
    }

    public static function get_type(int $id)
    {
        $type = Type::find($id);

        if (! $type) {
            return ApiService::errorResponse(
                'Тип не найден.',
                ApiService::TYPE_NOT_FOUND,
                [],
                404
            );
        }

        return ApiService::successResponse('Тип получен', self::formatType($type));
    }

    public static function edit_type(int $id, Request $request)
    {
        $type = Type::find($id);

        if (! $type) {
            return ApiService::errorResponse(
                'Тип не найден.',
                ApiService::TYPE_NOT_FOUND,
                [],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('types', 'name')->ignore($type->id)],
            'resource_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
        ], [
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Тип с таким названием уже существует.',
            'description.required' => 'Описание обязательно.',
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
            $type->name = $data['name'];
        }
        if (array_key_exists('resource_type', $data)) {
            $type->resource_type = $data['resource_type'];
        }
        if (array_key_exists('description', $data)) {
            $type->description = $data['description'];
        }

        $type->save();

        return ApiService::successResponse('Тип обновлён', self::formatType($type));
    }

    public static function delete_type(int $id)
    {
        $type = Type::find($id);

        if (! $type) {
            return ApiService::errorResponse(
                'Тип не найден.',
                ApiService::TYPE_NOT_FOUND,
                [],
                404
            );
        }

        $type->delete();

        return ApiService::successResponse('Тип удалён');
    }

    protected static function formatType(Type $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'resource_type' => $type->resource_type,
            'description' => $type->description,
            'created_at' => $type->created_at?->toISOString(),
            'updated_at' => $type->updated_at?->toISOString(),
        ];
    }
}

