<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rehersal;
use App\Models\User;

class RehersalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rehersal $rehersal): bool
    {
        return $this->owns($user, $rehersal);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rehersal $rehersal): bool
    {
        return $this->owns($user, $rehersal);
    }

    public function delete(User $user, Rehersal $rehersal): bool
    {
        return $this->owns($user, $rehersal);
    }

    private function owns(User $user, Rehersal $rehersal): bool
    {
        return $rehersal->owner_user_id !== null
            && (int) $rehersal->owner_user_id === (int) $user->id;
    }
}
