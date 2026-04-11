<?php

declare(strict_types=1);

namespace App\Services\Kanban;

use App\Enums\KanbanAccessLevel;
use App\Enums\KanbanVisibilityMode;
use App\Models\KanbanBoard;
use App\Models\KanbanCard;
use App\Models\KanbanColumn;
use App\Models\User;
use Illuminate\Support\Collection;

final class KanbanAccessService
{
    public function boardAccess(User $user, KanbanBoard $board): ?KanbanAccessLevel
    {
        if ((int) $board->user_id === (int) $user->id) {
            return KanbanAccessLevel::Editor;
        }

        $sharedUser = $board->sharedUsers()->where('users.id', $user->id)->first();
        if ($sharedUser !== null) {
            $pivotLevel = $sharedUser->pivot->access_level ?? KanbanAccessLevel::Editor->value;

            return KanbanAccessLevel::from((string) $pivotLevel);
        }

        return null;
    }

    public function canViewBoard(User $user, KanbanBoard $board): bool
    {
        return $this->boardAccess($user, $board) !== null;
    }

    public function canEditBoard(User $user, KanbanBoard $board): bool
    {
        $a = $this->boardAccess($user, $board);

        return $a !== null && $a->canEdit();
    }

    public function columnAccess(User $user, KanbanColumn $column): ?KanbanAccessLevel
    {
        $board = $column->board;
        if ((int) $board->user_id === (int) $user->id) {
            return KanbanAccessLevel::Editor;
        }

        $boardLevel = $this->boardAccess($user, $board);
        if ($boardLevel === null) {
            return null;
        }

        $mode = $column->visibility_mode instanceof KanbanVisibilityMode
            ? $column->visibility_mode
            : KanbanVisibilityMode::tryFrom((string) $column->visibility_mode) ?? KanbanVisibilityMode::Inherit;

        if ($mode === KanbanVisibilityMode::Inherit) {
            return $boardLevel;
        }

        $setBy = $column->visibility_set_by_user_id;
        if ($setBy !== null && (int) $setBy === (int) $user->id) {
            return KanbanAccessLevel::Editor;
        }

        return $this->maxGrantForUser($user, $column->grants);
    }

    public function canViewColumn(User $user, KanbanColumn $column): bool
    {
        return $this->columnAccess($user, $column) !== null;
    }

    public function canEditColumn(User $user, KanbanColumn $column): bool
    {
        $a = $this->columnAccess($user, $column);

        return $a !== null && $a->canEdit();
    }

    public function cardAccess(User $user, KanbanCard $card): ?KanbanAccessLevel
    {
        $column = $card->column;
        $board = $column->board;
        if ((int) $board->user_id === (int) $user->id) {
            return KanbanAccessLevel::Editor;
        }

        $colLevel = $this->columnAccess($user, $column);
        if ($colLevel === null) {
            return null;
        }

        $mode = $card->visibility_mode instanceof KanbanVisibilityMode
            ? $card->visibility_mode
            : KanbanVisibilityMode::tryFrom((string) $card->visibility_mode) ?? KanbanVisibilityMode::Inherit;

        if ($mode === KanbanVisibilityMode::Inherit) {
            return $colLevel;
        }

        $setBy = $card->visibility_set_by_user_id;
        if ($setBy !== null && (int) $setBy === (int) $user->id) {
            return KanbanAccessLevel::Editor;
        }

        $cardGrant = $this->maxGrantForUser($user, $card->grants);
        if ($cardGrant === null) {
            return null;
        }

        return KanbanAccessLevel::max($colLevel, $cardGrant);
    }

    public function canViewCard(User $user, KanbanCard $card): bool
    {
        return $this->cardAccess($user, $card) !== null;
    }

    public function canEditCard(User $user, KanbanCard $card): bool
    {
        $a = $this->cardAccess($user, $card);

        return $a !== null && $a->canEdit();
    }

    /**
     * @param  Collection<int, \App\Models\KanbanAccessGrant>  $grants
     */
    private function maxGrantForUser(User $user, Collection $grants): ?KanbanAccessLevel
    {
        $max = null;

        foreach ($grants as $grant) {
            $granteeType = (string) $grant->grantee_type;
            $granteeId = (int) $grant->grantee_id;

            if ($granteeType === User::class && $granteeId === (int) $user->id) {
                $max = KanbanAccessLevel::max($max, $grant->access_level);
            }
        }

        return $max;
    }
}
