<?php

declare(strict_types=1);

namespace App\Enums;

enum ShopDeliveryMode: string
{
    case Pickup = 'pickup';
    case Shipping = 'shipping';
}
