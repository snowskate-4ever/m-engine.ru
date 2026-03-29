<?php

declare(strict_types=1);

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\UserAiScheduledItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserAiScheduledItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', UserAiScheduledItem::class);

        $items = UserAiScheduledItem::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => $items->map(fn (UserAiScheduledItem $i) => $this->toArray($i)),
        ]);
    }

    public function destroy(Request $request, UserAiScheduledItem $item): JsonResponse
    {
        Gate::authorize('delete', $item);

        if ((int) $item->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $item->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(UserAiScheduledItem $item): array
    {
        return [
            'id' => $item->id,
            'kind' => $item->kind->value,
            'title' => $item->title,
            'payload' => $item->payload,
            'next_fire_at' => $item->next_fire_at?->toIso8601String(),
            'repeat_rule' => $item->repeat_rule,
            'notify_push' => $item->notify_push,
            'notify_email' => $item->notify_email,
            'status' => $item->status->value,
            'conversation_id' => $item->conversation_id,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    }
}
