<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use Livewire\Livewire;
use Tests\TestCase;

final class PublicDiscoverPageTest extends TestCase
{
    public function test_guest_can_open_public_discover(): void
    {
        $response = $this->get(route('discover'));

        $response->assertOk()
            ->assertSee(__('ui.music.discover_title'));
    }

    public function test_discover_includes_canonical_link(): void
    {
        $response = $this->get(route('discover'));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString(route('discover'), $html);
    }

    public function test_music_directory_livewire_disables_spa_navigate_on_public_page(): void
    {
        Livewire::test(\App\Livewire\Music\MusicDirectoryPage::class, ['spaNavigate' => false])
            ->assertSet('spaNavigate', false);
    }
}
