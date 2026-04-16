<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ProductMetricsService;
use App\Services\Music\MusicCalendarFeedService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MusicCalendarSyncController extends Controller
{
    public function feed(Request $request, MusicCalendarFeedService $calendarFeed, ProductMetricsService $metrics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'event_kind' => ['sometimes', 'string'],
            'owner_entity' => ['sometimes', 'string'],
        ]);

        $start = CarbonImmutable::parse((string) $validated['start'])->utc();
        $end = CarbonImmutable::parse((string) $validated['end'])->utc();
        $items = $calendarFeed->eventsForRange($user, $start, $end, $validated);

        $metrics->track('mobile.calendar_sync.feed_requested', $user->id, 'api', [
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'items_count' => $items->count(),
        ]);

        return response()->json([
            'data' => $items->map(static fn ($event): array => [
                'id' => $event->id,
                'name' => $event->name,
                'start_at' => $event->start_at?->toIso8601String(),
                'end_at' => $event->end_at?->toIso8601String(),
                'status' => $event->status,
                'booked_resource_id' => $event->booked_resource_id,
            ])->values(),
        ]);
    }

    public function connectors(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['provider' => 'google', 'status' => 'planned'],
                ['provider' => 'outlook', 'status' => 'planned'],
                ['provider' => 'ical', 'status' => 'available_export_only'],
            ],
        ]);
    }
}
