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
            // profiles
            'musician' => ['performer', 'studio', 'production', 'label', 'organizer', 'session', 'teacher', 'school', 'musician'],
            'session' => ['performer', 'studio', 'organizer', 'teacher'],
            'teacher' => ['school', 'musician', 'rehearsal', 'studio'],
            'organizer' => ['venue', 'performer', 'session', 'sound_engineer', 'live_sound', 'lighting_designer', 'videographer', 'photographer'],
            'agent' => ['*'],
            // entities
            'performer' => ['session', 'production', 'label', 'studio', 'rehearsal', 'organizer', 'agent', 'sound_engineer'],
            'studio' => ['musician', 'performer', 'teacher', 'sound_engineer', 'arranger', 'recording_engineer'],
            'rehearsal' => ['musician', 'performer', 'teacher', 'accountant'],
            'school' => ['teacher', 'musician', 'accountant'],
            'label' => ['performer', 'production', 'agent', 'studio', 'sound_engineer'],
            'production' => ['performer', 'studio', 'organizer', 'agent', 'session', 'sound_engineer'],
            'venue' => ['organizer', 'performer', 'session', 'live_sound', 'lighting_designer'],
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
