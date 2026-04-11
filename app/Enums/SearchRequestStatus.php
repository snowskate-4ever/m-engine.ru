<?php

declare(strict_types=1);

namespace App\Enums;

enum SearchRequestStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case AwaitingApproval = 'awaiting_approval';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Failed = 'failed';
}
