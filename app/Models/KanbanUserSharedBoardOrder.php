<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanUserSharedBoardOrder extends Model
{
    protected $table = 'kanban_user_shared_board_orders';

    protected $fillable = [
        'user_id',
        'kanban_board_id',
        'position',
    ];

    /**
     * @return BelongsTo<User, KanbanUserSharedBoardOrder>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<KanbanBoard, KanbanUserSharedBoardOrder>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'kanban_board_id');
    }
}
