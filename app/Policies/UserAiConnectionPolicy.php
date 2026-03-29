<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAiConnection;

class UserAiConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserAiConnection $connection): bool
    {
        return $user->id === $connection->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserAiConnection $connection): bool
    {
        return $user->id === $connection->user_id;
    }

    public function delete(User $user, UserAiConnection $connection): bool
    {
        return $user->id === $connection->user_id;
    }
}
