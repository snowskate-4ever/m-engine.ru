<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MusicDiscoverAndSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_category_page_ok_for_musician(): void
    {
        $this->get(route('discover.category', ['category' => 'musician']))
            ->assertOk()
            ->assertSee(__('ui.music.discover_category.musician'), false);
    }

    public function test_discover_category_unknown_returns_404(): void
    {
        $this->get(route('discover.category', ['category' => 'invalid']))
            ->assertNotFound();
    }

    public function test_sitemap_music_xml_contains_discover_and_public_profile_route_pattern(): void
    {
        $slug = 'smap-'.uniqid('', true);
        \App\Models\Teacher::query()->create([
            'name' => 'Sitemap Teacher',
            'description' => null,
            'slug' => $slug,
            'public_page_enabled' => true,
        ]);

        $xml = $this->get(route('sitemap.music'))->assertOk()->getContent();
        $this->assertStringContainsString(route('discover'), $xml);
        $this->assertStringContainsString(route('discover.category', ['category' => 'teacher']), $xml);
        $this->assertStringContainsString(route('public.teachers.show', ['slug' => $slug]), $xml);
    }

    public function test_robots_txt_lists_sitemap(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertStringContainsString('Sitemap:', $body);
        $this->assertStringContainsString('/sitemap-music.xml', $body);
    }
}
