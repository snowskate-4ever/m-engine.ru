<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Kanban\KanbanAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KanbanBoard extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'position',
    ];

    /**
     * @return BelongsTo<User, KanbanBoard>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<KanbanColumn>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(KanbanColumn::class)->orderBy('position');
    }

    /**
     * @return HasManyThrough<KanbanCard, KanbanColumn>
     */
    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(KanbanCard::class, KanbanColumn::class);
    }

    /**
     * @return BelongsToMany<User, KanbanBoard>
     */
    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kanban_board_user')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isAccessibleBy(User $user): bool
    {
        return app(KanbanAccessService::class)->canViewBoard($user, $this);
    }

    /**
     * @param  Builder<KanbanBoard>  $query
     * @return Builder<KanbanBoard>
     */
    public function scopeForUserAccess(Builder $query, User $user): Builder
    {
        $uid = (int) $user->id;

        return $query->where(function (Builder $q) use ($uid): void {
            $q->where('kanban_boards.user_id', $uid)
                ->orWhereHas(
                    'sharedUsers',
                    static fn (Builder $sq) => $sq->where('users.id', $uid),
                );
        });
    }
}
