<?php

declare(strict_types=1);

namespace App\Enums;

enum ShopPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Waived = 'waived';
}
