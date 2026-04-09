<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShopOrder;
use App\Models\User;

class ShopOrderPolicy
{
    public function view(User $user, ShopOrder $order): bool
    {
        return (int) $order->buyer_user_id === (int) $user->id
            || (int) $order->shop->owner_user_id === (int) $user->id;
    }

    public function confirmStore(User $user, ShopOrder $order): bool
    {
        return (int) $order->shop->owner_user_id === (int) $user->id;
    }
}
