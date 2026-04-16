<?php

declare(strict_types=1);

namespace App\Support\Music;

final class ActorTargetMatrix
{
    /**
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            // profile initiators
            'musician' => ['performer', 'studio', 'production', 'label', 'organizer', 'session', 'teacher', 'school', 'musician'],
            'session' => ['performer', 'studio', 'organizer', 'teacher'],
            'teacher' => ['school', 'musician', 'rehearsal', 'studio'],
            'organizer' => ['venue', 'performer', 'session', 'sound_engineer', 'live_sound', 'lighting_designer', 'videographer', 'photographer', 'promoter', 'merchandiser', 'tour_manager'],
            'agent' => ['*'],
            'sound_engineer' => ['studio', 'performer', 'production', 'label'],
            'arranger' => ['performer', 'studio', 'production'],
            'live_sound' => ['venue', 'organizer', 'performer'],
            'lighting_designer' => ['venue', 'organizer', 'performer'],
            'videographer' => ['performer', 'studio', 'organizer', 'venue'],
            'photographer' => ['performer', 'studio', 'organizer', 'venue'],
            'journalist' => ['performer', 'label', 'production', 'venue'],
            'venue_manager' => ['organizer', 'performer', 'session', 'live_sound', 'lighting_designer'],
            'merchandiser' => ['performer', 'organizer', 'label'],
            'tour_manager' => ['performer', 'venue', 'organizer', 'session'],
            'promoter' => ['venue', 'performer', 'organizer'],
            'recording_engineer' => ['studio', 'performer', 'production', 'label'],
            'mastering_engineer' => ['studio', 'performer', 'label'],
            'session_producer' => ['performer', 'studio', 'production'],
            'tech_rider' => ['venue', 'organizer', 'performer'],
            'backline_tech' => ['venue', 'performer', 'organizer'],
            'graphic_designer' => ['performer', 'label', 'production', 'organizer'],
            'smm_manager' => ['performer', 'label', 'production', 'organizer'],
            'music_lawyer' => ['performer', 'label', 'production', 'organizer', 'agent'],
            'accountant' => ['performer', 'studio', 'rehearsal', 'school', 'label', 'production', 'venue'],

            // entity initiators
            'performer' => ['session', 'production', 'label', 'studio', 'rehearsal', 'organizer', 'agent', 'sound_engineer', 'arranger', 'videographer', 'photographer', 'merchandiser', 'tour_manager', 'promoter', 'recording_engineer', 'mastering_engineer', 'session_producer', 'graphic_designer', 'smm_manager', 'music_lawyer', 'accountant'],
            'studio' => ['musician', 'performer', 'teacher', 'sound_engineer', 'arranger', 'videographer', 'photographer', 'recording_engineer', 'mastering_engineer', 'session_producer', 'accountant'],
            'rehearsal' => ['musician', 'performer', 'teacher', 'accountant'],
            'school' => ['teacher', 'musician', 'accountant'],
            'label' => ['performer', 'production', 'agent', 'studio', 'sound_engineer', 'videographer', 'photographer', 'journalist', 'recording_engineer', 'mastering_engineer', 'graphic_designer', 'smm_manager', 'music_lawyer', 'accountant'],
            'production' => ['performer', 'studio', 'organizer', 'agent', 'session', 'sound_engineer', 'arranger', 'videographer', 'recording_engineer', 'session_producer', 'graphic_designer', 'smm_manager', 'music_lawyer', 'accountant'],
            'venue' => ['organizer', 'performer', 'session', 'live_sound', 'lighting_designer', 'videographer', 'photographer', 'promoter', 'tour_manager', 'tech_rider', 'backline_tech', 'accountant'],
        ];
    }

    public static function allows(string $initiator, string $target): bool
    {
        $allowed = self::matrix()[$initiator] ?? [];
        if (in_array('*', $allowed, true)) {
            return true;
        }

        return in_array($target, $allowed, true);
    }
}
