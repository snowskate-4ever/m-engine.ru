<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Enums\KanbanAccessLevel;
use App\Models\CalendarEvent;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\User;
use App\Services\Kanban\KanbanAccessService;
use App\Services\Kanban\KanbanActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class EntityLinkedAccessService
{
    public function __construct(
        private readonly EntityOnCreateAutomationService $entityAutomation,
        private readonly KanbanAccessService $kanbanAccess,
    ) {}

    public function grantForMember(Model $entity, User $member): void
    {
        $entityType = $entity->getMorphClass();
        $entityId = (int) $entity->getKey();

        $board = KanbanBoard::query()
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->first();

        if ($board !== null && (int) $board->user_id !== (int) $member->id
            && ! $board->sharedUsers()->where('users.id', $member->id)->exists()) {
            $board->sharedUsers()->attach($member->id, ['access_level' => KanbanAccessLevel::Editor->value]);
        }

        $owner = $this->resolveEntityOwner($entity);
        if ($owner !== null) {
            $archiveBoard = $this->entityAutomation->ensureAccountArchiveBoard($owner);
            if (! $archiveBoard->sharedUsers()->where('users.id', $member->id)->exists()) {
                $archiveBoard->sharedUsers()->attach($member->id, ['access_level' => KanbanAccessLevel::Editor->value]);
            }
        }
    }

    public function revokeForMember(Model $entity, User $member): void
    {
        $entityType = $entity->getMorphClass();
        $entityId = (int) $entity->getKey();

        $board = KanbanBoard::query()
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->first();

        if ($board !== null) {
            $board->sharedUsers()->detach($member->id);
        }

        $owner = $this->resolveEntityOwner($entity);
        if ($owner !== null) {
            $archiveBoard = KanbanBoard::query()
                ->where('source_type', User::class)
                ->where('source_id', $owner->id)
                ->first();
            if ($archiveBoard !== null) {
                $archiveBoard->sharedUsers()->detach($member->id);
            }
        }
    }

    public function archiveCard(KanbanCard $card, User $actor): void
    {
        $card->loadMissing(['column.board', 'grants', 'column.grants']);
        if (! $this->kanbanAccess->canEditCard($actor, $card)) {
            return;
        }

        $sourceBoard = $card->column?->board;
        if ($sourceBoard === null) {
            return;
        }

        $archiveOwner = $this->resolveArchiveOwnerByBoard($sourceBoard);
        if ($archiveOwner === null) {
            return;
        }

        $archiveBoard = $this->entityAutomation->ensureAccountArchiveBoard($archiveOwner);
        $archiveColumn = $this->ensureArchiveColumnForSourceBoard($archiveBoard, $sourceBoard);

        if ((int) $card->kanban_column_id === (int) $archiveColumn->id) {
            return;
        }

        DB::transaction(function () use ($card, $archiveColumn, $archiveBoard, $sourceBoard, $actor): void {
            $targetPos = (int) KanbanCard::query()
                ->where('kanban_column_id', $archiveColumn->id)
                ->max('position');

            $fromColumn = $card->column;
            $card->update([
                'kanban_column_id' => $archiveColumn->id,
                'position' => $targetPos + 1,
                'is_archived' => true,
            ]);

            KanbanActivityLogger::log($actor, 'card_archived', (int) $archiveBoard->id, [
                'card_id' => $card->id,
                'from_board_id' => $sourceBoard->id,
                'from_column_id' => $fromColumn?->id,
                'to_board_id' => $archiveBoard->id,
                'to_column_id' => $archiveColumn->id,
            ]);
        });
    }

    public function cleanupForEntity(Model $entity): void
    {
        $entityType = $entity->getMorphClass();
        $entityId = (int) $entity->getKey();

        KanbanBoard::query()
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->delete();

        CalendarEvent::query()
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->delete();

    }

    private function resolveEntityOwner(Model $entity): ?User
    {
        $ownerId = (int) ($entity->getAttribute('owner_user_id') ?? $entity->getAttribute('user_id') ?? 0);
        if ($ownerId < 1) {
            return null;
        }

        return User::query()->find($ownerId);
    }

    private function resolveArchiveOwnerByBoard(KanbanBoard $board): ?User
    {
        if ($board->source_type === User::class && $board->source_id !== null) {
            return User::query()->find((int) $board->source_id);
        }

        if ($board->source_type !== null && $board->source_id !== null) {
            /** @var class-string<Model>|null $modelClass */
            $modelClass = $board->source_type;
            if ($modelClass !== null && class_exists($modelClass)) {
                $entity = $modelClass::query()->find((int) $board->source_id);
                if ($entity instanceof Model) {
                    return $this->resolveEntityOwner($entity);
                }
            }
        }

        return null;
    }

    private function ensureArchiveColumnForSourceBoard(KanbanBoard $archiveBoard, KanbanBoard $sourceBoard): KanbanColumn
    {
        $columnName = $this->archiveColumnName($sourceBoard);

        return KanbanColumn::query()->firstOrCreate(
            [
                'kanban_board_id' => $archiveBoard->id,
                'name' => $columnName,
            ],
            [
                'position' => (int) KanbanColumn::query()
                    ->where('kanban_board_id', $archiveBoard->id)
                    ->count(),
            ],
        );
    }

    private function archiveColumnName(KanbanBoard $sourceBoard): string
    {
        $name = trim((string) $sourceBoard->name);
        if ($name === '') {
            return 'Архив';
        }

        return mb_substr('Архив '.$name, 0, 255);
    }
}
