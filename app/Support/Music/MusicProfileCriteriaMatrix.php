<?php

declare(strict_types=1);

namespace App\Support\Music;

use App\Enums\MusicProfileCriteriaFormSink;
use App\Enums\UserMusicProfile;

/**
 * Единая матрица: какие критерии у профиля и где их редактируют в UI.
 *
 * @phpstan-type Criterion array{key: string, label_key: string, type: string}
 */
final class MusicProfileCriteriaMatrix
{
    /**
     * @return list<Criterion>
     */
    public static function criteria(UserMusicProfile $profile): array
    {
        return match ($profile) {
            UserMusicProfile::Musician,
            UserMusicProfile::SessionMusician => [
                ...self::defaultGeographyAndExperience(),
                ['key' => 'instruments', 'label_key' => 'ui.music.fields.instruments', 'type' => 'catalog_multi'],
                ['key' => 'genres', 'label_key' => 'ui.music.fields.genres', 'type' => 'catalog_multi'],
            ],
            default => self::defaultGeographyAndExperience(),
        };
    }

    /**
     * @return list<Criterion>
     */
    public static function defaultGeographyAndExperience(): array
    {
        return [
            ['key' => 'cities', 'label_key' => 'ui.music.fields.work_cities', 'type' => 'city_multi'],
            ['key' => 'years_of_experience', 'label_key' => 'ui.music.fields.years_of_experience', 'type' => 'integer'],
        ];
    }

    public static function formSink(UserMusicProfile $profile): MusicProfileCriteriaFormSink
    {
        return match ($profile) {
            UserMusicProfile::Musician => MusicProfileCriteriaFormSink::MusicianCard,
            UserMusicProfile::SessionMusician => MusicProfileCriteriaFormSink::SessionPointsMusicianCard,
            default => MusicProfileCriteriaFormSink::UserJson,
        };
    }

    /**
     * Строка вкладки на /profiles → enum профиля.
     */
    public static function profileFromTab(string $tab): ?UserMusicProfile
    {
        return match ($tab) {
            'organizer' => UserMusicProfile::EventOrganizer,
            'venue_manager' => UserMusicProfile::VenueManager,
            'musician' => UserMusicProfile::Musician,
            'teacher' => UserMusicProfile::Teacher,
            'manager' => UserMusicProfile::Manager,
            'session_musician' => UserMusicProfile::SessionMusician,
            default => UserMusicProfile::tryFrom($tab),
        };
    }

    /**
     * @return list<string>
     */
    public static function keys(UserMusicProfile $profile): array
    {
        return array_map(static fn (array $row): string => $row['key'], self::criteria($profile));
    }
}
