<?php

declare(strict_types=1);

namespace App\Http\Controllers\api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IntegrationTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:64'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:10', 'max:6000'],
        ]);

        $minted = IntegrationApiToken::mint(
            $user,
            $validated['name'],
            $validated['abilities'] ?? null,
            isset($validated['rate_limit_per_minute']) ? (int) $validated['rate_limit_per_minute'] : null,
        );

        return response()->json([
            'id' => $minted['token']->id,
            'plain_token' => $minted['plain'],
            'name' => $minted['token']->name,
            'abilities' => $minted['token']->abilities,
            'rate_limit_per_minute' => $minted['token']->rate_limit_per_minute,
        ], 201);
    }
}
