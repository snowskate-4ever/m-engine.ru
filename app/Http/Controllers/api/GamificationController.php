<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Gamification\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GamificationController extends Controller
{
    public function meXp(Request $request, GamificationService $gamification): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json([
            'total_xp' => $gamification->totalXp($user),
        ]);
    }

    public function leaderboard(Request $request, GamificationService $gamification): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        $limit = (int) $request->query('limit', '50');

        return response()->json([
            'leaderboard' => $gamification->leaderboard($limit),
        ]);
    }
}
