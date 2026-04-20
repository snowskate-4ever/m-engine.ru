<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Enums\UserMusicProfile;
use App\Livewire\Music\PublicPageSettingsModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicPageSettingsModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_user_profile_row_enables_event_organizer(): void
    {
        $user = User::factory()->create(['music_profiles' => []]);
        $this->actingAs($user);

        Livewire::test(PublicPageSettingsModal::class)
            ->call('toggleUserProfileRow', 'user_profile:event_organizer');

        $this->assertTrue($user->fresh()->hasMusicProfile(UserMusicProfile::EventOrganizer));
    }

    public function test_toggle_user_profile_row_enables_musician_and_creates_musician_record(): void
    {
        $user = User::factory()->create(['music_profiles' => []]);
        $this->actingAs($user);

        Livewire::test(PublicPageSettingsModal::class)
            ->call('toggleUserProfileRow', 'user_profile:musician');

        $user->refresh();
        $this->assertTrue($user->hasMusicProfile(UserMusicProfile::Musician));
        $this->assertNotNull($user->musician);
        $this->assertSame((int) $user->id, (int) $user->musician->user_id);
    }

    public function test_toggle_user_profile_row_enables_teacher_and_creates_teacher_record(): void
    {
        $user = User::factory()->create(['music_profiles' => []]);
        $this->actingAs($user);

        Livewire::test(PublicPageSettingsModal::class)
            ->call('toggleUserProfileRow', 'user_profile:teacher');

        $user->refresh();
        $this->assertTrue($user->hasMusicProfile(UserMusicProfile::Teacher));
        $this->assertNotNull($user->teacher);
        $this->assertSame((int) $user->id, (int) $user->teacher->user_id);
    }
}
