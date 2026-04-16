<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Conversation;
use App\Models\SearchRequest;
use App\Services\Analytics\ProductMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Протокол офлайн-синхронизации: манифест коллекций и курсоры для мобильного клиента.
 *
 * @see /docs/MOBILE_OFFLINE_SYNC.md
 */
final class MobileSyncController extends Controller
{
    public function manifest(Request $request, ProductMetricsService $metrics): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $since = $request->query('since');
        $sinceTs = is_string($since) && $since !== '' ? $since : null;

        $draftQuery = SearchRequest::query()
            ->where('created_by_user_id', $user->id)
            ->where('ad_status', AdStatus::Draft)
            ->orderByDesc('updated_at');

        if ($sinceTs !== null) {
            $draftQuery->where('updated_at', '>', $sinceTs);
        }

        $drafts = $draftQuery->limit(100)->get(['id', 'updated_at', 'ad_status']);
        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->when($sinceTs !== null, fn ($q) => $q->where('updated_at', '>', $sinceTs))
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['id', 'updated_at', 'type', 'title']);
        $calendarEvents = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->when($sinceTs !== null, fn ($q) => $q->where('updated_at', '>', $sinceTs))
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['id', 'updated_at', 'starts_at', 'ends_at', 'title', 'status']);

        $metrics->track('mobile.sync_manifest.requested', $user->id, 'mobile', [
            'since' => $sinceTs,
            'drafts_count' => $drafts->count(),
            'conversations_count' => $conversations->count(),
            'calendar_events_count' => $calendarEvents->count(),
        ]);

        return response()->json([
            'api_version' => 'mobile_sync_v1',
            'server_time' => now()->toIso8601String(),
            'collections' => [
                [
                    'name' => 'search_request_drafts',
                    'cursor_field' => 'updated_at',
                    'items' => $drafts,
                ],
                [
                    'name' => 'conversations',
                    'cursor_field' => 'updated_at',
                    'items' => $conversations,
                ],
                [
                    'name' => 'calendar_events',
                    'cursor_field' => 'updated_at',
                    'items' => $calendarEvents,
                ],
            ],
            'hints' => [
                'calendar' => 'GET /api/music/... (authenticated) — кешируйте ответы локально.',
                'kanban' => 'Используйте последний snapshot карточек до восстановления сети.',
            ],
        ]);
    }
}
