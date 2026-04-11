<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'kanban_board_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, KanbanActivityLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<KanbanBoard, KanbanActivityLog>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'kanban_board_id');
    }

    public static function labelForAction(string $action): string
    {
        return match ($action) {
            'board_created' => 'Создана доска',
            'column_created' => 'Создана колонка',
            'card_created' => 'Создана карточка',
            'card_updated' => 'Обновлена карточка',
            'card_comment_added' => 'Комментарий к карточке',
            'card_deleted' => 'Удалена карточка',
            'column_deleted' => 'Удалена колонка',
            'board_deleted' => 'Удалена доска',
            'boards_reordered' => 'Изменён порядок досок',
            'sync' => 'Перетаскивание (синхронизация доски)',
            'card_column_changed' => 'Карточка перенесена в колонку',
            'board_share_added' => 'Выдан доступ к доске',
            'board_share_removed' => 'Отозван доступ к доске',
            default => $action,
        };
    }
}
