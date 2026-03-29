<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiScheduledItemKind;
use App\Enums\AiScheduledItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAiScheduledItem extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'kind',
        'title',
        'payload',
        'next_fire_at',
        'repeat_rule',
        'notify_push',
        'notify_email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'kind' => AiScheduledItemKind::class,
            'status' => AiScheduledItemStatus::class,
            'payload' => 'array',
            'next_fire_at' => 'datetime',
            'notify_push' => 'boolean',
            'notify_email' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
