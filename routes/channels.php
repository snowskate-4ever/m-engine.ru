<?php

declare(strict_types=1);

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('messenger.conversation.{conversationId}', function ($user, string $conversationId) {
    return Conversation::query()
        ->whereKey((int) $conversationId)
        ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
        ->exists();
});
