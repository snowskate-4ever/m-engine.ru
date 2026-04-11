<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\KanbanCardAttachment;
use App\Services\Kanban\KanbanAccessService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KanbanCardAttachmentController extends Controller
{
    public function download(KanbanCardAttachment $attachment): StreamedResponse|Response
    {
        $attachment->loadMissing(['card.column.board', 'card.grants', 'card.column.grants']);
        $card = $attachment->card;
        if ($card === null) {
            abort(404);
        }

        $board = $card->column?->board;
        if ($board === null) {
            abort(403);
        }

        $access = app(KanbanAccessService::class);
        if (! $access->canViewCard(request()->user(), $card)) {
            abort(403);
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
        );
    }
}
