<?php

declare(strict_types=1);

namespace App\Support\Music;

use App\Enums\UserMusicProfile;

/**
 * Описание настраиваемых критериев по типу музыкого профиля (поиск, карточки, анкеты).
 *
 * @phpstan-type Criterion array{key: string, label_key: string, type: string}
 */
final class MusicProfileCriteria
{
    /**
     * @return list<Criterion>
     */
    public static function defaultGeographyAndExperience(): array
    {
        return MusicProfileCriteriaMatrix::defaultGeographyAndExperience();
    }

    /**
     * @return list<Criterion>
     */
    public static function for(UserMusicProfile $profile): array
    {
        return MusicProfileCriteriaMatrix::criteria($profile);
    }

    /**
     * @return list<string>
     */
    public static function keys(UserMusicProfile $profile): array
    {
        return MusicProfileCriteriaMatrix::keys($profile);
    }
}
