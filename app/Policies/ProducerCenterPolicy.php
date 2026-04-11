<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProducerCenter;
use App\Models\User;

class ProducerCenterPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProducerCenter $producerCenter): bool
    {
        return $this->owns($user, $producerCenter);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProducerCenter $producerCenter): bool
    {
        return $this->owns($user, $producerCenter);
    }

    public function delete(User $user, ProducerCenter $producerCenter): bool
    {
        return $this->owns($user, $producerCenter);
    }

    private function owns(User $user, ProducerCenter $producerCenter): bool
    {
        return $producerCenter->owner_user_id !== null
            && (int) $producerCenter->owner_user_id === (int) $user->id;
    }
}
