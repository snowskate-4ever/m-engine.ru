<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\UserAiPreference;
use App\Services\Ai\AiServerQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class UserAiPreferenceController extends Controller
{
    public function __construct(
        private readonly AiServerQuotaService $quota,
    ) {}

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        if (! array_key_exists('max_requests_per_day_self', $request->all())) {
            $existing = UserAiPreference::query()->where('user_id', $user->id)->value('max_requests_per_day_self');

            return response()->json([
                'data' => [
                    'max_requests_per_day_self' => $existing,
                ],
            ]);
        }

        $validated = $request->validate([
            'max_requests_per_day_self' => ['nullable', 'integer', 'min:1'],
        ]);

        $value = $validated['max_requests_per_day_self'];
        if ($value !== null) {
            $ceiling = $this->quota->serverSideDailyCeiling($user);
            if ($ceiling !== null && $value > $ceiling) {
                throw ValidationException::withMessages([
                    'max_requests_per_day_self' => [__('validation.max.numeric', ['attribute' => 'max_requests_per_day_self', 'max' => $ceiling])],
                ]);
            }
            $maxSelf = max(1, (int) config('billing.max_self_cap_requests_per_day', 100_000));
            if ($ceiling === null && $value > $maxSelf) {
                throw ValidationException::withMessages([
                    'max_requests_per_day_self' => [__('validation.max.numeric', ['attribute' => 'max_requests_per_day_self', 'max' => $maxSelf])],
                ]);
            }
        }

        UserAiPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['max_requests_per_day_self' => $value],
        );

        return response()->json([
            'data' => [
                'max_requests_per_day_self' => $value,
            ],
        ]);
    }
}
