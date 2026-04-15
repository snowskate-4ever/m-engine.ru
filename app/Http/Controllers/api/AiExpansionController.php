<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Ai\Expansion\ContentModerationScorer;
use App\Services\Ai\Expansion\PartnerRecommender;
use App\Services\Ai\Expansion\StudioLoadForecaster;
use App\Services\Ai\Expansion\SupportChatResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AiExpansionController extends Controller
{
    public function moderationScore(Request $request, ContentModerationScorer $scorer): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        $validated = $request->validate(['text' => ['required', 'string', 'max:20000']]);

        return response()->json($scorer->scoreText($validated['text']));
    }

    public function recommendPartners(Request $request, PartnerRecommender $recommender): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json(['items' => $recommender->recommendForUser($user)]);
    }

    public function studioForecast(Request $request, StudioLoadForecaster $forecaster): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        $validated = $request->validate(['studio_id' => ['required', 'integer', 'min:1']]);

        return response()->json(['series' => $forecaster->forecastNextDay((int) $validated['studio_id'])]);
    }

    public function supportChat(Request $request, SupportChatResponder $responder): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        $validated = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        return response()->json(['reply' => $responder->reply($validated['message'])]);
    }
}
