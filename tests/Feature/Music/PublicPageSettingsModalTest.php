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

        Livewire::test(PublicPageSettingsModal::class, ['panel' => 'user_profiles'])
            ->call('toggleUserProfileRow', 'user_profile:event_organizer');

        $this->assertTrue($user->fresh()->hasMusicProfile(UserMusicProfile::EventOrganizer));
    }

    public function test_toggle_user_profile_row_enables_musician_and_creates_musician_record(): void
    {
        $user = User::factory()->create(['music_profiles' => []]);
        $this->actingAs($user);

        Livewire::test(PublicPageSettingsModal::class, ['panel' => 'user_profiles'])
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

        Livewire::test(PublicPageSettingsModal::class, ['panel' => 'user_profiles'])
            ->call('toggleUserProfileRow', 'user_profile:teacher');

        $user->refresh();
        $this->assertTrue($user->hasMusicProfile(UserMusicProfile::Teacher));
        $this->assertNotNull($user->teacher);
        $this->assertSame((int) $user->id, (int) $user->teacher->user_id);
    }

    public function test_user_profiles_panel_only_contains_user_profile_row_keys(): void
    {
        $user = User::factory()->create(['music_profiles' => []]);
        $this->actingAs($user);

        $component = Livewire::test(PublicPageSettingsModal::class, ['panel' => 'user_profiles']);
        foreach (array_keys($component->get('rows')) as $key) {
            $this->assertStringStartsWith('user_profile:', $key);
        }
    }

    public function test_public_pages_panel_has_no_user_profile_row_keys(): void
    {
        $user = User::factory()->create(['music_profiles' => []]);
        $this->actingAs($user);

        $component = Livewire::test(PublicPageSettingsModal::class, ['panel' => 'public_pages']);
        $keys = array_keys($component->get('rows'));
        $userProfileKeys = array_filter($keys, static fn (string $key): bool => str_starts_with($key, 'user_profile:'));
        $this->assertSame([], array_values($userProfileKeys));
    }
}
