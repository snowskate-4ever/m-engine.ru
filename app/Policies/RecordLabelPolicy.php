<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecordLabel;
use App\Models\User;

class RecordLabelPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, RecordLabel $recordLabel): bool
    {
        return $this->owns($user, $recordLabel);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, RecordLabel $recordLabel): bool
    {
        return $this->owns($user, $recordLabel);
    }

    public function delete(User $user, RecordLabel $recordLabel): bool
    {
        return $this->owns($user, $recordLabel);
    }

    private function owns(User $user, RecordLabel $recordLabel): bool
    {
        return $recordLabel->owner_user_id !== null
            && (int) $recordLabel->owner_user_id === (int) $user->id;
    }
}
