<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\CalendarEvent;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class EntityOnCreateAutomationService
{
    /** @var list<string> */
    private const DEFAULT_COLUMNS = ['Входящие', 'К выполнению', 'В работе', 'На паузе', 'Готово'];

    private const COMMON_BOARD_NAME = 'Моя доска';
    private const ARCHIVE_BOARD_NAME = 'Архив';
    private const ARCHIVE_COLUMN_NAME = 'Архив';

    public function run(Model $entity, User $owner): void
    {
        DB::transaction(function () use ($entity, $owner): void {
            $this->ensureCommonBoard($owner);
            $this->ensureAccountArchiveBoard($owner);
            $entityBoard = $this->ensureEntityBoard($entity, $owner);
            $todoColumn = $this->ensureTodoColumn($entityBoard);
            $this->ensureStarterCard($entity, $todoColumn);
            $this->ensureCalendarEvent($entity, $owner);
        });
    }

    public function ensureAccountArchiveBoard(User $owner): KanbanBoard
    {
        $systemUser = $this->systemUser();
        $board = KanbanBoard::query()
            ->where('source_type', User::class)
            ->where('source_id', $owner->id)
            ->first();

        if ($board === null) {
            $board = KanbanBoard::query()->create([
                'user_id' => $systemUser->id,
                'name' => self::ARCHIVE_BOARD_NAME,
                'position' => (int) KanbanBoard::query()->where('user_id', $systemUser->id)->count(),
                'source_type' => User::class,
                'source_id' => $owner->id,
            ]);
        }

        KanbanColumn::query()->firstOrCreate(
            [
                'kanban_board_id' => $board->id,
                'name' => self::ARCHIVE_COLUMN_NAME,
            ],
            [
                'position' => 0,
            ],
        );

        $this->ensureEditorAccess($board, $owner);

        return $board;
    }

    private function ensureCommonBoard(User $owner): KanbanBoard
    {
        $board = KanbanBoard::query()->firstOrCreate(
            [
                'user_id' => $owner->id,
                'name' => self::COMMON_BOARD_NAME,
            ],
            [
                'position' => (int) KanbanBoard::query()->where('user_id', $owner->id)->count(),
            ],
        );

        $this->ensureDefaultColumns($board);

        return $board;
    }

    private function ensureEntityBoard(Model $entity, User $owner): KanbanBoard
    {
        $entityType = $entity->getMorphClass();
        $entityId = (int) $entity->getKey();
        $board = KanbanBoard::query()
            ->where('source_type', $entityType)
            ->where('source_id', $entityId)
            ->first();

        if ($board === null) {
            $systemUser = $this->systemUser();
            $board = KanbanBoard::query()->create([
                'user_id' => $systemUser->id,
                'name' => $this->entityBoardName($entity),
                'position' => (int) KanbanBoard::query()->where('user_id', $systemUser->id)->count(),
                'source_type' => $entityType,
                'source_id' => $entityId,
            ]);
        }

        $this->ensureDefaultColumns($board);
        $this->ensureEditorAccess($board, $owner);

        return $board;
    }

    private function ensureEditorAccess(KanbanBoard $board, User $user): void
    {
        if (! $board->sharedUsers()->where('users.id', $user->id)->exists()) {
            $board->sharedUsers()->attach($user->id, ['access_level' => 'editor']);
        }
    }

    private function systemUser(): User
    {
        $email = (string) config('app.system_user_email', 'system-kanban@m-engine.local');

        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'System',
                'password' => bcrypt(bin2hex(random_bytes(16))),
            ],
        );
    }

    private function ensureDefaultColumns(KanbanBoard $board): void
    {
        foreach (self::DEFAULT_COLUMNS as $index => $name) {
            KanbanColumn::query()->firstOrCreate(
                [
                    'kanban_board_id' => $board->id,
                    'name' => $name,
                ],
                [
                    'position' => $index,
                ],
            );
        }
    }

    private function ensureTodoColumn(KanbanBoard $board): KanbanColumn
    {
        return KanbanColumn::query()->firstOrCreate(
            [
                'kanban_board_id' => $board->id,
                'name' => self::DEFAULT_COLUMNS[1],
            ],
            [
                'position' => 1,
            ],
        );
    }

    private function ensureStarterCard(Model $entity, KanbanColumn $column): KanbanCard
    {
        $existing = KanbanCard::query()
            ->where('source_type', $entity->getMorphClass())
            ->where('source_id', (int) $entity->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return KanbanCard::query()->create([
            'kanban_column_id' => $column->id,
            'title' => $this->starterCardTitle($entity),
            'description' => $this->starterCardDescription($entity),
            'position' => (int) $column->cards()->count(),
            'source_type' => $entity->getMorphClass(),
            'source_id' => (int) $entity->getKey(),
        ]);
    }

    private function ensureCalendarEvent(Model $entity, User $owner): CalendarEvent
    {
        $sourceType = $entity->getMorphClass();
        $sourceId = (int) $entity->getKey();

        $existing = CalendarEvent::query()
            ->where('user_id', $owner->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $tz = (string) config('app.timezone');
        $startsAt = CarbonImmutable::now($tz)->addMinutes(15);
        $endsAt = $startsAt->addMinutes(30);

        return CalendarEvent::query()->create([
            'user_id' => $owner->id,
            'created_by' => $owner->id,
            'is_public' => false,
            'title' => $this->calendarEventTitle($entity),
            'description' => $this->calendarEventDescription($entity),
            'starts_at' => $startsAt->utc(),
            'ends_at' => $endsAt->utc(),
            'all_day' => false,
            'reminder_minutes' => 15,
            'color' => CalendarEvent::DEFAULT_EVENT_COLOR,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'status' => 'planned',
            'timezone' => $tz,
        ]);
    }

    private function entityBoardName(Model $entity): string
    {
        return sprintf('%s: %s', $this->entityTypeLabel($entity), $this->entityDisplayName($entity));
    }

    private function starterCardTitle(Model $entity): string
    {
        return sprintf('Добавление %s', $this->entityDisplayName($entity));
    }

    private function starterCardDescription(Model $entity): string
    {
        return sprintf(
            'Проверить и заполнить профиль (%s, #%d).',
            $this->entityTypeLabel($entity),
            (int) $entity->getKey()
        );
    }

    private function calendarEventTitle(Model $entity): string
    {
        return sprintf('Проверить новую сущность: %s', $this->entityDisplayName($entity));
    }

    private function calendarEventDescription(Model $entity): string
    {
        return sprintf(
            'Автособытие после создания сущности (%s, #%d).',
            $this->entityTypeLabel($entity),
            (int) $entity->getKey()
        );
    }

    private function entityDisplayName(Model $entity): string
    {
        $name = (string) ($entity->getAttribute('name') ?? '');
        if ($name !== '') {
            return $name;
        }

        return sprintf('%s #%d', class_basename($entity), (int) $entity->getKey());
    }

    private function entityTypeLabel(Model $entity): string
    {
        return match (class_basename($entity)) {
            'Peformer' => 'Исполнитель',
            'Studio' => 'Студия',
            'Rehersal' => 'Репетиционная',
            'ConcertVenue' => 'Площадка',
            'School' => 'Школа',
            'RecordLabel' => 'Лейбл',
            'ProducerCenter' => 'Продюсерский центр',
            'Shop' => 'Магазин',
            default => 'Сущность',
        };
    }
}
