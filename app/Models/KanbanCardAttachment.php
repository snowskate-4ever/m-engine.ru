<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KanbanCardAttachment extends Model
{
    protected $fillable = [
        'kanban_card_id',
        'user_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<KanbanCard, KanbanCardAttachment>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(KanbanCard::class, 'kanban_card_id');
    }

    /**
     * @return BelongsTo<User, KanbanCardAttachment>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
