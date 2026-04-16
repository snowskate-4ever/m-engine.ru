<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ProductMetricsService;
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

        app(ProductMetricsService::class)->track('ai.moderation_score.requested', $user->id, 'api');

        return response()->json($scorer->scoreText($validated['text']));
    }

    public function recommendPartners(Request $request, PartnerRecommender $recommender): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        app(ProductMetricsService::class)->track('ai.partner_recommend.requested', $user->id, 'api');

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

        $qualityGate = (bool) config('ai_expansion.support_chat_quality_gate', true);
        app(ProductMetricsService::class)->track('ai.support_chat.requested', $user->id, 'api', [
            'quality_gate' => $qualityGate,
            'message_length' => mb_strlen($validated['message']),
        ]);

        $reply = $responder->reply($validated['message']);
        if ($qualityGate && mb_strlen(trim($reply)) < 10) {
            $reply = 'Уточните запрос, чтобы я дал точную рекомендацию.';
        }

        return response()->json(['reply' => $reply]);
    }

    public function composeContent(Request $request, SupportChatResponder $responder): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);
        $validated = $request->validate([
            'content_type' => ['required', 'string', 'in:description,poster,post,other'],
            'brief' => ['required', 'string', 'max:4000'],
        ]);

        app(ProductMetricsService::class)->track('ai.content_assistant.requested', $user->id, 'api', [
            'content_type' => $validated['content_type'],
        ]);

        return response()->json([
            'content' => $responder->composeContent(
                (string) $validated['content_type'],
                (string) $validated['brief'],
            ),
        ]);
    }
}
