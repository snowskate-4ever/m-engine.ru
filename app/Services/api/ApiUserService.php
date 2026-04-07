<?php

namespace App\Services\api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiUserService
{
    public static function get_users(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'telegram_id' => 'sometimes|integer',
            'vk_user_id' => 'sometimes|integer',
            'id' => 'sometimes|integer|exists:users,id',
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

        $query = User::query()->orderBy('id');

        if (isset($filters['telegram_id'])) {
            $query->where('telegram_id', $filters['telegram_id']);
        }
        if (isset($filters['vk_user_id'])) {
            $query->where('vk_user_id', $filters['vk_user_id']);
        }
        if (isset($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        $users = $query->get()->map(fn (User $user) => self::formatUser($user));

        return ApiService::successResponse('Список пользователей получен', ['users' => $users]);
    }

    public static function get_user(int $id)
    {
        $user = User::find($id);

        if (! $user) {
            return ApiService::errorResponse(
                'Пользователь не найден.',
                ApiService::USER_NOT_FOUND,
                [],
                404
            );
        }

        return ApiService::successResponse('Пользователь получен', self::formatUser($user));
    }

    protected static function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'registration_channel' => $user->registration_channel,
            'registration_metadata' => $user->registration_metadata,
            'telegram_id' => $user->telegram_id,
            'vk_user_id' => $user->vk_user_id,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }
}
