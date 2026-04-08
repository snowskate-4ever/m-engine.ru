<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Studio;
use App\Models\User;

class StudioPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Studio $studio): bool
    {
        return $this->owns($user, $studio);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Studio $studio): bool
    {
        return $this->owns($user, $studio);
    }

    public function delete(User $user, Studio $studio): bool
    {
        return $this->owns($user, $studio);
    }

    private function owns(User $user, Studio $studio): bool
    {
        return $studio->owner_user_id !== null
            && (int) $studio->owner_user_id === (int) $user->id;
    }
}
