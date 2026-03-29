<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentToolInvocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'tool_name',
        'arguments_hash',
        'ok',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'created_at' => 'datetime',
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
