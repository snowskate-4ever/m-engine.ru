<?php

declare(strict_types=1);

namespace App\Services\Kanban;

use App\Models\KanbanActivityLog;
use App\Models\User;

final class KanbanActivityLogger
{
    public static function log(User $user, string $action, ?int $kanbanBoardId = null, ?array $payload = null): void
    {
        KanbanActivityLog::query()->create([
            'user_id' => $user->id,
            'action' => $action,
            'kanban_board_id' => $kanbanBoardId,
            'payload' => $payload,
        ]);
    }
}
