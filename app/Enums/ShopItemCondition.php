<?php

declare(strict_types=1);

namespace App\Enums;

enum ShopItemCondition: string
{
    case New = 'new';
    case Used = 'used';
}
