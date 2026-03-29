<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversationRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $table = 'conversation_user';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_message_id',
        'joined_at',
        'notifications_muted',
        'mute_until',
    ];

    protected function casts(): array
    {
        return [
            'role' => ConversationRole::class,
            'joined_at' => 'datetime',
            'notifications_muted' => 'boolean',
            'mute_until' => 'datetime',
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

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }
}
