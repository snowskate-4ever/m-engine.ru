<?php

declare(strict_types=1);

namespace App\Enums;

enum MusicMembershipRole: string
{
    case VenueRepresentative = 'venue_representative';
    case Manager = 'manager';
}
