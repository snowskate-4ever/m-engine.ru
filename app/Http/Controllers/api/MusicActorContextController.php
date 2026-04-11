<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Music\MusicActorContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MusicActorContextController extends Controller
{
    public function __construct(
        private readonly MusicActorContextService $actorContextService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->actorContextService->availableActors($request->user()),
            'current' => $this->actorContextService->currentActor($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string'],
            'id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['type']) || empty($validated['id'])) {
            $request->user()->setActiveMusicActor(null, null);

            return response()->json(['ok' => true, 'current' => null]);
        }

        $this->actorContextService->setActiveActor($request->user(), (string) $validated['type'], (int) $validated['id']);

        return response()->json([
            'ok' => true,
            'current' => $this->actorContextService->currentActor($request->user()->fresh()),
        ]);
    }
}
