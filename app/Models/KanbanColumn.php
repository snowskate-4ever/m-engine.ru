<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KanbanVisibilityMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class KanbanColumn extends Model
{
    protected $fillable = [
        'kanban_board_id',
        'name',
        'position',
        'visibility_mode',
        'visibility_set_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility_mode' => KanbanVisibilityMode::class,
        ];
    }

    /**
     * @return BelongsTo<KanbanBoard, KanbanColumn>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(KanbanBoard::class, 'kanban_board_id');
    }

    /**
     * @return BelongsTo<User, KanbanColumn>
     */
    public function visibilitySetter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visibility_set_by_user_id');
    }

    /**
     * @return MorphMany<KanbanAccessGrant>
     */
    public function grants(): MorphMany
    {
        return $this->morphMany(KanbanAccessGrant::class, 'subject');
    }

    /**
     * @return HasMany<KanbanCard>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(KanbanCard::class)->orderBy('position');
    }
}
