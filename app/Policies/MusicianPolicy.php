<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Musician;
use App\Models\User;

class MusicianPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Musician $musician): bool
    {
        return $musician->user_id !== null && (int) $musician->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->musician === null;
    }

    public function update(User $user, Musician $musician): bool
    {
        return $musician->user_id !== null && (int) $musician->user_id === (int) $user->id;
    }

    public function delete(User $user, Musician $musician): bool
    {
        return $this->update($user, $musician);
    }
}
