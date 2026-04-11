<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\KanbanUserSharedBoardOrder;
use App\Services\Kanban\KanbanAccessService;
use App\Services\Kanban\KanbanActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KanbanController extends Controller
{
    public function index(): View
    {
        return view('kanban');
    }

    public function sync(Request $request, KanbanBoard $board): JsonResponse
    {
        $user = $request->user();
        $access = app(KanbanAccessService::class);

        if (! $access->canViewBoard($user, $board)) {
            abort(403);
        }

        $validated = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['array'],
            'columns.*.*' => ['integer', 'exists:kanban_cards,id'],
            'column_order' => ['sometimes', 'array'],
            'column_order.*' => ['integer', 'exists:kanban_columns,id'],
        ]);

        $columns = $board->columns()->with('grants')->orderBy('position')->get();

        $viewableColumnIds = $columns
            ->filter(static fn (KanbanColumn $c) => $access->canViewColumn($user, $c))
            ->pluck('id')
            ->map(static fn ($id) => (string) $id)
            ->sort()
            ->values()
            ->all();

        $actual = collect($validated['columns'])->keys()->map(static fn ($k) => (string) $k)->sort()->values()->all();

        if ($viewableColumnIds !== $actual) {
            abort(422, 'Нужно передать все колонки, видимые вам на доске');
        }

        $viewableCards = KanbanCard::query()
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $board->id))
            ->with(['grants', 'column.grants', 'column.board'])
            ->get()
            ->filter(static fn (KanbanCard $c) => $access->canViewCard($user, $c));

        $viewableCardIds = $viewableCards->pluck('id')->map(static fn ($id) => (int) $id)->sort()->values()->all();

        $mentionedIds = [];
        foreach ($validated['columns'] as $cardIds) {
            foreach ($cardIds as $cid) {
                $mentionedIds[] = (int) $cid;
            }
        }

        if (count($mentionedIds) !== count(array_unique($mentionedIds))) {
            abort(422, 'Карточка указана дважды');
        }

        sort($mentionedIds);
        $mentionedIds = array_values($mentionedIds);

        if ($mentionedIds !== $viewableCardIds) {
            abort(422, 'Неполный порядок карточек');
        }

        foreach ($viewableCards as $card) {
            if (! $access->canEditCard($user, $card)) {
                abort(403, 'Нет права менять карточки на этой доске');
            }
        }

        $viewableColumnIdsInt = $columns
            ->filter(static fn (KanbanColumn $c) => $access->canViewColumn($user, $c))
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if (isset($validated['column_order'])) {
            if (! $access->canEditBoard($user, $board)) {
                abort(403, 'Нет права менять порядок колонок');
            }

            $allColumnCount = $columns->count();
            $viewableColumnCount = $columns->filter(static fn (KanbanColumn $c) => $access->canViewColumn($user, $c))->count();
            if ($viewableColumnCount !== $allColumnCount) {
                abort(422, 'Порядок колонок нельзя менять, пока часть колонок скрыта от вас');
            }

            $order = array_values(array_map(static fn ($id) => (int) $id, $validated['column_order']));
            if (count($order) !== count(array_unique($order))) {
                abort(422, 'Колонка указана дважды в порядке');
            }

            $sortedOrder = $order;
            sort($sortedOrder);
            $sortedViewable = $viewableColumnIdsInt;
            sort($sortedViewable);
            if ($sortedViewable !== $sortedOrder) {
                abort(422, 'Неверный набор колонок в column_order');
            }
        }

        $cardsBefore = KanbanCard::query()
            ->whereHas('column', static fn ($q) => $q->where('kanban_board_id', $board->id))
            ->get(['id', 'kanban_column_id', 'position'])
            ->keyBy('id');

        $columnIdsOrderedBefore = $board->columns()->orderBy('position')->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        DB::transaction(function () use ($validated, $board): void {
            foreach ($validated['columns'] as $columnId => $cardIds) {
                $columnIdInt = (int) $columnId;
                foreach ($cardIds as $position => $cardId) {
                    KanbanCard::query()->whereKey($cardId)->update([
                        'kanban_column_id' => $columnIdInt,
                        'position' => $position,
                    ]);
                }
            }
            if (isset($validated['column_order'])) {
                $order = array_values(array_map(static fn ($id) => (int) $id, $validated['column_order']));
                foreach ($order as $position => $columnId) {
                    KanbanColumn::query()
                        ->whereKey($columnId)
                        ->where('kanban_board_id', $board->id)
                        ->update(['position' => $position]);
                }
            }
        });

        foreach ($validated['columns'] as $columnId => $cardIds) {
            $columnIdInt = (int) $columnId;
            foreach ($cardIds as $cardId) {
                $cardIdInt = (int) $cardId;
                $prev = $cardsBefore->get($cardIdInt);
                if ($prev === null) {
                    continue;
                }
                if ((int) $prev->kanban_column_id === $columnIdInt) {
                    continue;
                }
                $fromCol = $columns->firstWhere('id', (int) $prev->kanban_column_id);
                $toCol = $columns->firstWhere('id', $columnIdInt);
                KanbanActivityLogger::log($request->user(), 'card_column_changed', (int) $board->id, [
                    'card_id' => $cardIdInt,
                    'from_column_id' => (int) $prev->kanban_column_id,
                    'to_column_id' => $columnIdInt,
                    'from_column_name' => $fromCol !== null ? $fromCol->name : null,
                    'to_column_name' => $toCol !== null ? $toCol->name : null,
                ]);
            }
        }

        $cardsSlotChanges = 0;
        foreach ($validated['columns'] as $columnId => $cardIds) {
            foreach ($cardIds as $position => $cardId) {
                $prev = $cardsBefore->get($cardId);
                if ($prev === null) {
                    continue;
                }
                if ((int) $prev->kanban_column_id !== (int) $columnId
                    || (int) $prev->position !== (int) $position) {
                    $cardsSlotChanges++;
                }
            }
        }

        $columnOrderChanged = false;
        if (isset($validated['column_order'])) {
            $newOrder = array_values(array_map(static fn ($id) => (int) $id, $validated['column_order']));
            $columnOrderChanged = $newOrder !== $columnIdsOrderedBefore;
        }

        if ($cardsSlotChanges > 0 || $columnOrderChanged) {
            KanbanActivityLogger::log($request->user(), 'sync', (int) $board->id, [
                'cards_slot_changes' => $cardsSlotChanges,
                'column_order_changed' => $columnOrderChanged,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function reorderBoards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board_ids' => ['required', 'array'],
            'board_ids.*' => ['integer', 'exists:kanban_boards,id'],
        ]);

        $userId = (int) $request->user()->id;
        $owned = KanbanBoard::query()
            ->where('user_id', $userId)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $given = array_values(array_map(static fn ($id) => (int) $id, $validated['board_ids']));
        if (count($given) !== count(array_unique($given))) {
            abort(422, 'Доска указана дважды');
        }
        $givenSorted = $given;
        sort($givenSorted);
        if ($owned !== $givenSorted) {
            abort(422, 'Неверный набор досок');
        }

        $boardOrderBefore = KanbanBoard::query()
            ->where('user_id', $userId)
            ->orderBy('position')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();

        DB::transaction(function () use ($given, $userId): void {
            foreach ($given as $position => $boardId) {
                KanbanBoard::query()
                    ->where('user_id', $userId)
                    ->whereKey($boardId)
                    ->update(['position' => $position]);
            }
        });

        if ($given !== $boardOrderBefore) {
            KanbanActivityLogger::log($request->user(), 'boards_reordered', null, [
                'board_ids' => $given,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function reorderSharedBoards(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board_ids' => ['required', 'array'],
            'board_ids.*' => ['integer', 'exists:kanban_boards,id'],
        ]);

        $user = $request->user();
        $userId = (int) $user->id;

        $accessibleShared = KanbanBoard::query()
            ->forUserAccess($user)
            ->where('user_id', '!=', $userId)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $given = array_values(array_map(static fn ($id) => (int) $id, $validated['board_ids']));
        if (count($given) !== count(array_unique($given))) {
            abort(422, 'Доска указана дважды');
        }
        $givenSorted = $given;
        sort($givenSorted);
        if ($accessibleShared !== $givenSorted) {
            abort(422, 'Неверный набор досок');
        }

        $orderRows = KanbanUserSharedBoardOrder::query()
            ->where('user_id', $userId)
            ->whereIn('kanban_board_id', $given)
            ->get()
            ->keyBy(static fn (KanbanUserSharedBoardOrder $r) => (int) $r->kanban_board_id);

        $nameById = KanbanBoard::query()
            ->whereIn('id', $given)
            ->pluck('name', 'id')
            ->map(static fn ($name) => (string) $name)
            ->all();

        $beforeIds = collect($given)
            ->sortBy(function (int $id) use ($orderRows, $nameById): string {
                $row = $orderRows->get($id);
                $pos = $row !== null ? (int) $row->position : PHP_INT_MAX;

                return sprintf('%020d|%s', $pos, $nameById[$id] ?? '');
            })
            ->values()
            ->map(static fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($given, $userId): void {
            foreach ($given as $position => $boardId) {
                KanbanUserSharedBoardOrder::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'kanban_board_id' => $boardId,
                    ],
                    ['position' => $position],
                );
            }

            KanbanUserSharedBoardOrder::query()
                ->where('user_id', $userId)
                ->whereNotIn('kanban_board_id', $given)
                ->delete();
        });

        if ($beforeIds !== $given) {
            KanbanActivityLogger::log($user, 'shared_boards_reordered', null, [
                'board_ids' => $given,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
