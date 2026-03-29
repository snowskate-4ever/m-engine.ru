<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\PushPlatform;
use App\Http\Controllers\Controller;
use App\Models\DevicePushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DevicePushTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', Rule::enum(PushPlatform::class)],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $user = $request->user();

        DevicePushToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $user->id,
                'platform' => PushPlatform::from($validated['platform']),
                'app_version' => $validated['app_version'] ?? null,
            ],
        );

        return response()->json(['ok' => true]);
    }
}
