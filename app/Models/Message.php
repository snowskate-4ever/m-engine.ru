<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'kind',
        'body',
        'is_forward',
        'forwarded_from_message_id',
        'forward_snapshot',
        'client_message_id',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MessageKind::class,
            'is_forward' => 'boolean',
            'forward_snapshot' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function forwardedFrom(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'forwarded_from_message_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
