<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, School $school): bool
    {
        return $this->owns($user, $school);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, School $school): bool
    {
        return $this->owns($user, $school);
    }

    public function delete(User $user, School $school): bool
    {
        return $this->owns($user, $school);
    }

    private function owns(User $user, School $school): bool
    {
        return $school->owner_user_id !== null
            && (int) $school->owner_user_id === (int) $user->id;
    }
}
