<?php

declare(strict_types=1);

namespace App\Enums;

enum UserMusicProfile: string
{
    case Musician = 'musician';
    case Teacher = 'teacher';
    case EventOrganizer = 'event_organizer';
    case VenueRepresentative = 'venue_representative';
    case Manager = 'manager';
    case SessionMusician = 'session_musician';
    case Agent = 'agent';
    case SoundEngineer = 'sound_engineer';
    case Arranger = 'arranger';
    case LiveSound = 'live_sound';
    case LightingDesigner = 'lighting_designer';
    case Videographer = 'videographer';
    case Photographer = 'photographer';
    case Journalist = 'journalist';
    case VenueManager = 'venue_manager';
    case Merchandiser = 'merchandiser';
    case TourManager = 'tour_manager';
    case Promoter = 'promoter';
    case RecordingEngineer = 'recording_engineer';
    case MasteringEngineer = 'mastering_engineer';
    case SessionProducer = 'session_producer';
    case TechRider = 'tech_rider';
    case BacklineTech = 'backline_tech';
    case GraphicDesigner = 'graphic_designer';
    case SmmManager = 'smm_manager';
    case MusicLawyer = 'music_lawyer';
    case Accountant = 'accountant';
}
