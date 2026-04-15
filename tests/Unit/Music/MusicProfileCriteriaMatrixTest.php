<?php

declare(strict_types=1);

namespace Tests\Unit\Music;

use App\Enums\MusicProfileCriteriaFormSink;
use App\Enums\UserMusicProfile;
use App\Support\Music\MusicProfileCriteriaMatrix;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MusicProfileCriteriaMatrixTest extends TestCase
{
    public function test_session_musician_sink_points_to_musician_card(): void
    {
        $this->assertSame(
            MusicProfileCriteriaFormSink::SessionPointsMusicianCard,
            MusicProfileCriteriaMatrix::formSink(UserMusicProfile::SessionMusician),
        );
    }

    public function test_organizer_sink_is_user_json(): void
    {
        $this->assertSame(
            MusicProfileCriteriaFormSink::UserJson,
            MusicProfileCriteriaMatrix::formSink(UserMusicProfile::EventOrganizer),
        );
    }

    #[DataProvider('tabProfileMap')]
    public function test_profile_from_tab_maps_special_tabs(string $tab, UserMusicProfile $expected): void
    {
        $this->assertSame($expected, MusicProfileCriteriaMatrix::profileFromTab($tab));
    }

    /**
     * @return iterable<string, array{0: string, 1: UserMusicProfile}>
     */
    public static function tabProfileMap(): iterable
    {
        yield 'organizer' => ['organizer', UserMusicProfile::EventOrganizer];
        yield 'venue_manager' => ['venue_manager', UserMusicProfile::VenueManager];
        yield 'musician' => ['musician', UserMusicProfile::Musician];
    }
}
