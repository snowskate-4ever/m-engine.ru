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

    public function test_tab_select_lists_only_enabled_profiles_when_any_enabled(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['teacher', 'event_organizer'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class)
            ->assertViewHas('tabOptions', function (array $options): bool {
                return array_column($options, 'value') === ['teacher', 'organizer'];
            });
    }

    public function test_when_no_profiles_enabled_select_lists_all_tabs(): void
    {
        $user = User::factory()->create([
            'music_profiles' => [],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class)
            ->assertViewHas('tabOptions', function (array $options): bool {
                return count($options) === 26;
            });
    }

    public function test_url_tab_not_enabled_resets_to_first_enabled_tab(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['agent'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class, ['tab' => 'musician'])
            ->assertSet('tab', 'agent');
    }

    public function test_url_tab_not_enabled_resets_to_first_of_multiple_enabled(): void
    {
        $user = User::factory()->create([
            'music_profiles' => ['event_organizer', 'teacher'],
        ]);

        Livewire::actingAs($user)->test(MusicProfilesPage::class, ['tab' => 'agent'])
            ->assertSet('tab', 'teacher');
    }
}
