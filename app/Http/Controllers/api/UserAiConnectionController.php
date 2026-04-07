<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\UserAiConnection;
use App\Support\AiChatDrivers;
use App\Support\SecretMask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserAiConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', UserAiConnection::class);

        $items = UserAiConnection::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $items->map(fn (UserAiConnection $c) => $this->toPublicArray($c)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', UserAiConnection::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['required', 'string', 'max:64', Rule::in(AiChatDrivers::keys())],
            'credentials' => ['required', 'array'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        $connection = UserAiConnection::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'driver' => $validated['driver'],
            'credentials' => $validated['credentials'],
            'enabled' => $validated['enabled'] ?? true,
        ]);

        return response()->json([
            'data' => $this->toPublicArray($connection->fresh()),
        ], 201);
    }

    public function update(Request $request, UserAiConnection $connection): JsonResponse
    {
        Gate::authorize('update', $connection);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'driver' => ['sometimes', 'string', 'max:64', Rule::in(AiChatDrivers::keys())],
            'credentials' => ['sometimes', 'array'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $validated)) {
            $connection->name = $validated['name'];
        }
        if (array_key_exists('driver', $validated)) {
            $connection->driver = $validated['driver'];
        }
        if (array_key_exists('credentials', $validated)) {
            $connection->credentials = $validated['credentials'];
        }
        if (array_key_exists('enabled', $validated)) {
            $connection->enabled = $validated['enabled'];
        }

        $connection->save();

        return response()->json([
            'data' => $this->toPublicArray($connection->fresh()),
        ]);
    }

    public function destroy(UserAiConnection $connection): JsonResponse
    {
        Gate::authorize('delete', $connection);

        $connection->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toPublicArray(UserAiConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'driver' => $connection->driver,
            'enabled' => $connection->enabled,
            'key_hint' => SecretMask::hintFromCredentials($connection->credentials),
            'last_used_at' => $connection->last_used_at?->toIso8601String(),
            'created_at' => $connection->created_at?->toIso8601String(),
            'updated_at' => $connection->updated_at?->toIso8601String(),
        ];
    }
}
