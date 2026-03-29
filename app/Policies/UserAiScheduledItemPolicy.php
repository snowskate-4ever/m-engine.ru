<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAiScheduledItem;

class UserAiScheduledItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserAiScheduledItem $item): bool
    {
        return (int) $item->user_id === (int) $user->id;
    }

    public function delete(User $user, UserAiScheduledItem $item): bool
    {
        return (int) $item->user_id === (int) $user->id;
    }
}
