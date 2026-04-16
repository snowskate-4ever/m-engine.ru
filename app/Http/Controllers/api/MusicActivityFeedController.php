<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\SearchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MusicActivityFeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'target_kind' => ['sometimes', 'string', 'max:40'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $limit = (int) ($validated['limit'] ?? 20);

        $recentReviews = Review::query()
            ->whereNotNull('verified_booking_id')
            ->latest()
            ->limit($limit)
            ->get(['id', 'reviewable_type', 'reviewable_id', 'rating', 'created_at']);

        $recentRequestsQuery = SearchRequest::query()
            ->where('ad_status', 'active')
            ->where('moderation_status', 'approved')
            ->latest('published_at');
        if (isset($validated['target_kind'])) {
            $recentRequestsQuery->where('target_kind', (string) $validated['target_kind']);
        } elseif (($user->active_music_actor_type ?? null) !== null) {
            // Lightweight personalization by actor type.
            $actorType = (string) $user->active_music_actor_type;
            if (str_contains($actorType, 'Musician')) {
                $recentRequestsQuery->whereIn('target_kind', ['musician', 'session', 'teacher']);
            }
            if (str_contains($actorType, 'Peformer')) {
                $recentRequestsQuery->whereIn('target_kind', ['performer', 'agent']);
            }
        }

        $recentRequests = $recentRequestsQuery
            ->limit($limit)
            ->get(['id', 'target_kind', 'published_at', 'created_by_user_id']);

        return response()->json([
            'data' => [
                'verified_reviews' => $recentReviews,
                'active_search_requests' => $recentRequests,
            ],
        ]);
    }
}
