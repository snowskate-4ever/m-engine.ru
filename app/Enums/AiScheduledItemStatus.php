<?php

declare(strict_types=1);

namespace App\Enums;

enum AiScheduledItemStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
