<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCardComment extends Model
{
    protected $fillable = [
        'kanban_card_id',
        'user_id',
        'body',
    ];

    /**
     * @return BelongsTo<KanbanCard, KanbanCardComment>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(KanbanCard::class, 'kanban_card_id');
    }

    /**
     * @return BelongsTo<User, KanbanCardComment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
