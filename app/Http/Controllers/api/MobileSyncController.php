<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Enums\AdStatus;
use App\Http\Controllers\Controller;
use App\Models\SearchRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Протокол офлайн-синхронизации: манифест коллекций и курсоры для мобильного клиента.
 *
 * @see /docs/MOBILE_OFFLINE_SYNC.md
 */
final class MobileSyncController extends Controller
{
    public function manifest(Request $request): JsonResponse
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

        return response()->json([
            'api_version' => 'mobile_sync_v1',
            'server_time' => now()->toIso8601String(),
            'collections' => [
                [
                    'name' => 'search_request_drafts',
                    'cursor_field' => 'updated_at',
                    'items' => $drafts,
                ],
            ],
            'hints' => [
                'calendar' => 'GET /api/music/... (authenticated) — кешируйте ответы локально.',
                'kanban' => 'Используйте последний snapshot карточек до восстановления сети.',
            ],
        ]);
    }
}
