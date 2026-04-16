<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Services\Analytics\ProductMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MusicReviewController extends Controller
{
    public function storeVerified(Request $request, ProductMetricsService $metrics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'reviewable_type' => ['required', 'string', 'max:160'],
            'reviewable_id' => ['required', 'integer', 'min:1'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking = Booking::query()->findOrFail((int) $validated['booking_id']);
        $isOwner = (int) $booking->booked_by_user_id === (int) $user->id;
        if (! $isOwner) {
            abort(403, 'Only booking owner can leave verified review.');
        }
        if (! in_array((string) $booking->status, ['confirmed', 'completed'], true) || ($booking->ends_at?->isFuture() ?? true)) {
            return response()->json(['message' => 'Verified review is allowed only for finished bookings.'], 422);
        }

        $review = Review::query()->create([
            'author_user_id' => $user->id,
            'verified_booking_id' => $booking->id,
            'reviewable_type' => (string) $validated['reviewable_type'],
            'reviewable_id' => (int) $validated['reviewable_id'],
            'contextable_type' => Booking::class,
            'contextable_id' => $booking->id,
            'rating' => (int) $validated['rating'],
            'body' => isset($validated['body']) ? (string) $validated['body'] : null,
            'moderation_status' => 'pending',
        ]);

        $metrics->track('ugc.verified_review.created', $user->id, 'api', [
            'review_id' => $review->id,
            'booking_id' => $booking->id,
            'rating' => $review->rating,
        ]);

        return response()->json([
            'data' => [
                'id' => $review->id,
                'verified_booking_id' => $review->verified_booking_id,
                'rating' => $review->rating,
                'moderation_status' => $review->moderation_status,
            ],
        ], 201);
    }
}
