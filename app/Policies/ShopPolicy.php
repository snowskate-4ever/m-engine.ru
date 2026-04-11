<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shop $shop): bool
    {
        return $this->owns($user, $shop);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Shop $shop): bool
    {
        return $this->owns($user, $shop);
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $this->owns($user, $shop);
    }

    private function owns(User $user, Shop $shop): bool
    {
        return $shop->owner_user_id !== null
            && (int) $shop->owner_user_id === (int) $user->id;
    }
}
