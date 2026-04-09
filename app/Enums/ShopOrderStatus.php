<?php

declare(strict_types=1);

namespace App\Enums;

enum ShopOrderStatus: string
{
    case Pending = 'pending';
    case StoreConfirmed = 'store_confirmed';
    case Cancelled = 'cancelled';
}
