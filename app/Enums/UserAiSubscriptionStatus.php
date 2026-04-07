<?php

declare(strict_types=1);

namespace App\Enums;

enum UserAiSubscriptionStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case PastDue = 'past_due';
}
