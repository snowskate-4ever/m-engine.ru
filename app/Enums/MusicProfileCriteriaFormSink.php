<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Где на /profiles редактируются критерии (города, стаж и т.д.).
 */
enum MusicProfileCriteriaFormSink: string
{
    /** Полная карточка музыканта (инструменты, жанры, города, опыт). */
    case MusicianCard = 'musician_card';

    /** Те же критерии, что у музыканта — редактируются только на вкладке «Музыкант». */
    case SessionPointsMusicianCard = 'session_points_musician_card';

    /** JSON users.music_profile_criteria[profile_key] + при необходимости синхронизация (например, города преподавателя). */
    case UserJson = 'user_json';
}
