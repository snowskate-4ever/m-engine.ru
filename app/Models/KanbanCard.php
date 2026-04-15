<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KanbanCardImportance;
use App\Enums\KanbanVisibilityMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KanbanCard extends Model
{
    protected $fillable = [
        'kanban_column_id',
        'title',
        'description',
        'importance',
        'position',
        'due_at',
        'visibility_mode',
        'visibility_set_by_user_id',
        'source_chat_message_id',
        'source_type',
        'source_id',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'importance' => KanbanCardImportance::class,
            'visibility_mode' => KanbanVisibilityMode::class,
            'due_at' => 'immutable_datetime',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KanbanColumn, KanbanCard>
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(KanbanColumn::class, 'kanban_column_id');
    }

    /**
     * @return BelongsTo<Message, KanbanCard>
     */
    public function sourceChatMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'source_chat_message_id');
    }

    /**
     * @return BelongsTo<User, KanbanCard>
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

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<KanbanCardComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(KanbanCardComment::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<KanbanCardAttachment>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(KanbanCardAttachment::class)->orderBy('created_at');
    }
}
