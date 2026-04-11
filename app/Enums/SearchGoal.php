<?php

declare(strict_types=1);

namespace App\Enums;

enum SearchGoal: string
{
    case FindMusicianForPerformer = 'find_musician_for_performer';
    case FindPerformerForMusician = 'find_performer_for_musician';
    case FindOrganizerForPerformer = 'find_organizer_for_performer';
    case FindPerformerForOrganizer = 'find_performer_for_organizer';
    case FindVenueForOrganizerEvent = 'find_venue_for_organizer_event';
    case FindOrganizerForVenue = 'find_organizer_for_venue';
    case FindStudioForOrganizerEvent = 'find_studio_for_organizer_event';
    case FindOrganizerForStudio = 'find_organizer_for_studio';
    case FindRehearsalForOrganizerEvent = 'find_rehearsal_for_organizer_event';
    case FindOrganizerForRehearsal = 'find_organizer_for_rehearsal';
    case FindSchoolForOrganizerEvent = 'find_school_for_organizer_event';
    case FindOrganizerForSchool = 'find_organizer_for_school';
}
