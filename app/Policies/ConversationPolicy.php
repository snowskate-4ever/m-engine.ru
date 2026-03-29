<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('users.id', $user->id)->exists();
    }

    public function participate(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
