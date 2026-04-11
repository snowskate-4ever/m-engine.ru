<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KanbanActivityLog;
use Illuminate\Contracts\View\View;

class KanbanLogsController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $logs = KanbanActivityLog::query()
            ->with(['user:id,name,email', 'board:id,name,user_id'])
            ->where(function ($outer) use ($user): void {
                $outer->where(function ($q) use ($user): void {
                    $q->whereNotNull('kanban_board_id')
                        ->whereHas('board', static fn ($b) => $b->forUserAccess($user));
                })->orWhere(function ($q) use ($user): void {
                    $q->whereNull('kanban_board_id')
                        ->where('user_id', $user->id);
                });
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('kanban-logs', [
            'logs' => $logs,
        ]);
    }
}
