<?php

declare(strict_types=1);

namespace App\Enums;

enum MatchingInviteStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Left = 'left';
    case Revoked = 'revoked';
}
