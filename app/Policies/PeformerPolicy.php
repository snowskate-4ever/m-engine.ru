<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Peformer;
use App\Models\User;

class PeformerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Peformer $peformer): bool
    {
        return $this->manages($user, $peformer);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Peformer $peformer): bool
    {
        return $this->manages($user, $peformer);
    }

    public function delete(User $user, Peformer $peformer): bool
    {
        return $peformer->owner_user_id !== null
            && (int) $peformer->owner_user_id === (int) $user->id;
    }

    public function manageMembers(User $user, Peformer $peformer): bool
    {
        return $this->manages($user, $peformer);
    }

    private function manages(User $user, Peformer $peformer): bool
    {
        if ($peformer->owner_user_id !== null && (int) $peformer->owner_user_id === (int) $user->id) {
            return true;
        }

        return $peformer->admins()->where('users.id', $user->id)->exists();
    }
}
