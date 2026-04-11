<?php

declare(strict_types=1);

namespace App\Services\Kanban;

use App\Models\KanbanActivityLog;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\User;
use App\Models\UserKanbanCalendarSetting;
use App\Support\Calendar\CalendarGridEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class KanbanCalendarEventService
{
    public function __construct(
        private KanbanAccessService $access,
    ) {}

    /**
     * @return Collection<int, CalendarGridEntry>
     */
    public function collect(User $user, CarbonImmutable $startUtc, CarbonImmutable $endUtc): Collection
    {
        $settings = UserKanbanCalendarSetting::forUser($user);
        $access = $this->access;

        $boardIds = KanbanBoard::query()
            ->forUserAccess($user)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if ($boardIds === []) {
            return collect();
        }

        $entries = collect();

        if ($settings->show_card_created_events) {
            KanbanCard::query()
                ->whereHas('column', static fn ($q) => $q->whereIn('kanban_board_id', $boardIds))
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<=', $endUtc)
                ->with(['column.board'])
                ->orderBy('created_at')
                ->chunkById(200, function ($chunk) use ($user, $entries, $access): void {
                    foreach ($chunk as $card) {
                        /** @var KanbanCard $card */
                        if (! $access->canViewCard($user, $card)) {
                            continue;
                        }
                        $column = $card->column;
                        $board = $column?->board;
                        $at = CarbonImmutable::parse($card->created_at)->utc();
                        $tooltip = self::tooltipBoardColumn($board?->name, $column?->name, 'Создание карточки');
                        $entries->push(CalendarGridEntry::kanbanPoint(
                            'created',
                            (int) $card->id,
                            $at,
                            $card->title,
                            $tooltip,
                        ));
                    }
                });
        }

        if ($settings->show_due_events) {
            KanbanCard::query()
                ->whereHas('column', static fn ($q) => $q->whereIn('kanban_board_id', $boardIds))
                ->whereNotNull('due_at')
                ->where('due_at', '>=', $startUtc)
                ->where('due_at', '<=', $endUtc)
                ->with(['column.board'])
                ->orderBy('due_at')
                ->chunkById(200, function ($chunk) use ($user, $entries, $access): void {
                    foreach ($chunk as $card) {
                        /** @var KanbanCard $card */
                        if (! $access->canViewCard($user, $card)) {
                            continue;
                        }
                        $column = $card->column;
                        $board = $column?->board;
                        $at = CarbonImmutable::parse($card->due_at)->utc();
                        $tooltip = self::tooltipBoardColumn($board?->name, $column?->name, 'Срок');
                        $entries->push(CalendarGridEntry::kanbanPoint(
                            'due',
                            (int) $card->id,
                            $at,
                            $card->title,
                            $tooltip,
                        ));
                    }
                });
        }

        if ($settings->show_column_move_events) {
            $targetWhitelist = null;
            if (! $settings->column_moves_include_all_targets) {
                $raw = $settings->column_move_target_ids ?? [];
                $targetWhitelist = array_fill_keys(array_map(static fn ($v) => (int) $v, $raw), true);
            }

            KanbanActivityLog::query()
                ->where('action', 'card_column_changed')
                ->whereIn('kanban_board_id', $boardIds)
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<=', $endUtc)
                ->orderBy('created_at')
                ->chunkById(200, function ($chunk) use ($user, $boardIds, $targetWhitelist, $entries, $access): void {
                    foreach ($chunk as $log) {
                        /** @var KanbanActivityLog $log */
                        $bid = $log->kanban_board_id !== null ? (int) $log->kanban_board_id : null;
                        if ($bid === null || ! in_array($bid, $boardIds, true)) {
                            continue;
                        }
                        $payload = $log->payload ?? [];
                        $cardId = isset($payload['card_id']) ? (int) $payload['card_id'] : 0;
                        if ($cardId < 1) {
                            continue;
                        }
                        $toColumnId = isset($payload['to_column_id']) ? (int) $payload['to_column_id'] : 0;
                        if ($targetWhitelist !== null && ($toColumnId < 1 || ! isset($targetWhitelist[$toColumnId]))) {
                            continue;
                        }

                        $card = KanbanCard::query()
                            ->whereKey($cardId)
                            ->with(['column.board'])
                            ->first();
                        if ($card === null || ! $access->canViewCard($user, $card)) {
                            continue;
                        }
                        $at = CarbonImmutable::parse($log->created_at)->utc();
                        $boardName = $card->column?->board?->name;
                        $lines = array_filter([
                            'Перенос в колонку',
                            ($payload['to_column_name'] ?? null) !== null && $payload['to_column_name'] !== ''
                                ? '→ '.$payload['to_column_name']
                                : null,
                            $boardName !== null && $boardName !== '' ? 'Доска: '.$boardName : null,
                            ($payload['from_column_name'] ?? null) !== null && $payload['from_column_name'] !== ''
                                ? 'Из: '.$payload['from_column_name']
                                : null,
                            'Карточка: '.$card->title,
                        ]);
                        $tooltip = implode("\n", $lines);
                        $short = '→ '.(string) ($payload['to_column_name'] ?? 'колонка').' · '.$card->title;
                        if (mb_strlen($short) > 80) {
                            $short = mb_substr($short, 0, 77).'…';
                        }
                        $entries->push(CalendarGridEntry::kanbanPoint(
                            'move',
                            $cardId,
                            $at,
                            $short,
                            $tooltip,
                            'kb-move-'.$log->id,
                        ));
                    }
                });
        }

        return $entries
            ->sort(static function (CalendarGridEntry $a, CalendarGridEntry $b): int {
                $byTime = $a->startsAtUtc <=> $b->startsAtUtc;

                return $byTime !== 0 ? $byTime : strcmp($a->sortKey, $b->sortKey);
            })
            ->values();
    }

    private static function tooltipBoardColumn(?string $boardName, ?string $columnName, string $prefix): string
    {
        $lines = array_filter([
            $prefix,
            $boardName !== null && $boardName !== '' ? 'Доска: '.$boardName : null,
            $columnName !== null && $columnName !== '' ? 'Колонка: '.$columnName : null,
        ]);

        return implode("\n", $lines);
    }
}
