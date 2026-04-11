<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $teacher->user_id !== null && (int) $teacher->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->teacher === null;
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $teacher->user_id !== null && (int) $teacher->user_id === (int) $user->id;
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->update($user, $teacher);
    }
}
