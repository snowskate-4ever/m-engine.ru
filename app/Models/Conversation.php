<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'title',
        'created_by_user_id',
        'retention_days',
        'ai_server_model_id',
        'user_ai_connection_id',
        'direct_peer_min_id',
        'direct_peer_max_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
            'retention_days' => 'integer',
            'ai_server_model_id' => 'integer',
            'user_ai_connection_id' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function directPeerMin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_peer_min_id');
    }

    public function directPeerMax(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_peer_max_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->using(ConversationUser::class)
            ->withPivot([
                'role',
                'last_read_message_id',
                'joined_at',
                'notifications_muted',
                'mute_until',
            ])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function conversationUsers(): HasMany
    {
        return $this->hasMany(ConversationUser::class);
    }

    public function aiServerModel(): BelongsTo
    {
        return $this->belongsTo(AiServerModel::class, 'ai_server_model_id');
    }

    public function userAiConnection(): BelongsTo
    {
        return $this->belongsTo(UserAiConnection::class, 'user_ai_connection_id');
    }
}
