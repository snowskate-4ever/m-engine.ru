<?php

declare(strict_types=1);

namespace App\Http\Controllers\api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationApiToken;
use App\Services\Analytics\ProductMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

final class IntegrationTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $rows = IntegrationApiToken::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get(['id', 'name', 'abilities', 'rate_limit_per_minute', 'last_used_at', 'revoked_at', 'created_at']);

        return response()->json(['data' => $rows]);
    }

    public function destroy(Request $request, IntegrationApiToken $integrationApiToken, ProductMetricsService $metrics): Response
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($integrationApiToken->user_id === $user->id, 404);

        if ($integrationApiToken->revoked_at === null) {
            $integrationApiToken->forceFill(['revoked_at' => now()])->save();
            $metrics->track('integration.token.revoked', $user->id, 'integration', [
                'token_id' => $integrationApiToken->id,
            ]);
        }

        return response()->noContent();
    }

    public function rotate(Request $request, IntegrationApiToken $integrationApiToken, ProductMetricsService $metrics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless($integrationApiToken->user_id === $user->id, 404);

        if ($integrationApiToken->revoked_at !== null) {
            return response()->json(['message' => 'Cannot rotate a revoked token.'], 422);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:128'],
        ]);

        $newName = $validated['name'] ?? $integrationApiToken->name.' (rotated)';
        if (strlen($newName) > 128) {
            $newName = substr($newName, 0, 128);
        }

        $minted = DB::transaction(function () use ($user, $integrationApiToken, $newName): array {
            $integrationApiToken->forceFill(['revoked_at' => now()])->save();

            return IntegrationApiToken::mint(
                $user,
                $newName,
                is_array($integrationApiToken->abilities) ? $integrationApiToken->abilities : null,
                $integrationApiToken->rate_limit_per_minute !== null
                    ? (int) $integrationApiToken->rate_limit_per_minute
                    : null,
            );
        });

        $metrics->track('integration.token.rotated', $user->id, 'integration', [
            'old_token_id' => $integrationApiToken->id,
            'new_token_id' => $minted['token']->id,
        ]);

        return response()->json([
            'id' => $minted['token']->id,
            'plain_token' => $minted['plain'],
            'name' => $minted['token']->name,
            'abilities' => $minted['token']->abilities,
            'rate_limit_per_minute' => $minted['token']->rate_limit_per_minute,
            'rotated_from_token_id' => $integrationApiToken->id,
        ], 201);
    }

    public function store(Request $request, ProductMetricsService $metrics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:64', Rule::in((array) config('integration.allowed_abilities', ['*']))],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:10', 'max:6000'],
        ]);

        $minted = IntegrationApiToken::mint(
            $user,
            $validated['name'],
            $validated['abilities'] ?? null,
            isset($validated['rate_limit_per_minute']) ? (int) $validated['rate_limit_per_minute'] : null,
        );

        $metrics->track('integration.token.minted', $user->id, 'integration', [
            'token_id' => $minted['token']->id,
            'abilities' => $minted['token']->abilities ?? [],
            'rate_limit_per_minute' => $minted['token']->rate_limit_per_minute,
        ]);

        return response()->json([
            'id' => $minted['token']->id,
            'plain_token' => $minted['plain'],
            'name' => $minted['token']->name,
            'abilities' => $minted['token']->abilities,
            'rate_limit_per_minute' => $minted['token']->rate_limit_per_minute,
        ], 201);
    }
}
