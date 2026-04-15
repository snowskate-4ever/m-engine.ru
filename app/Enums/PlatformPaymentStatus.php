<?php

declare(strict_types=1);

namespace App\Enums;

enum PlatformPaymentStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Captured = 'captured';
    case EscrowHeld = 'escrow_held';
    case Released = 'released';
    case RefundedPartial = 'refunded_partial';
    case RefundedFull = 'refunded_full';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
