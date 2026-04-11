<?php

declare(strict_types=1);

namespace App\Enums;

enum MusicMembershipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Revoked = 'revoked';
}
