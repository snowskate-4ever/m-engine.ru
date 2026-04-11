<?php

declare(strict_types=1);

namespace App\Enums;

enum UserMusicProfile: string
{
    case EventOrganizer = 'event_organizer';
    case VenueRepresentative = 'venue_representative';
    case Manager = 'manager';
}
