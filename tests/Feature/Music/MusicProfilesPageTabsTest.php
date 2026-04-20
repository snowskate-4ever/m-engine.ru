<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Livewire\Music\MusicProfilesPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MusicProfilesPageTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_switcher_lists_only_enabled_profile_tabs(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['teacher', 'event_organizer'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class)
            ->assertViewHas('enabledTabOptions', function (array $options): bool {
                return array_column($options, 'value') === ['teacher', 'organizer'];
            })
            ->assertViewHas('hasAnyEnabledProfile', true);
    }

    public function test_when_no_profiles_enabled_switcher_is_empty_and_hint_shown(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class)
            ->assertViewHas('hasAnyEnabledProfile', false)
            ->assertViewHas('enabledTabOptions', []);
    }

    public function test_url_tab_not_in_enabled_coerces_to_first_enabled(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['agent'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class, ['tab' => 'musician'])
            ->assertSet('tab', 'agent')
            ->assertSet('quickSwitchTab', 'agent');
    }

    public function test_url_tab_not_in_enabled_coerces_with_multiple_enabled(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer', 'teacher'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class, ['tab' => 'agent'])
            ->assertSet('tab', 'teacher')
            ->assertSet('quickSwitchTab', 'teacher');
    }

    public function test_switching_quick_switch_tab_updates_active_profile_tab(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['teacher', 'event_organizer'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class, ['tab' => 'teacher'])
            ->set('quickSwitchTab', 'organizer')
            ->assertSet('tab', 'organizer');
    }
}
