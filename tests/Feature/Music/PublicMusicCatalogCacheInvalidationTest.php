<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\Teacher;
use App\Services\Music\MusicPublicSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicMusicCatalogCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_counts_refresh_after_new_public_teacher_without_waiting_ttl(): void
    {
        MusicPublicSearchService::forgetPublicCatalogCountsCache();

        Teacher::query()->create([
            'name' => 'First Teacher',
            'description' => null,
            'slug' => 'first-t-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $counts1 = app(MusicPublicSearchService::class)->publicCatalogCounts();
        $before = $counts1['teacher'];

        Teacher::query()->create([
            'name' => 'Second Teacher',
            'description' => null,
            'slug' => 'second-t-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $counts2 = app(MusicPublicSearchService::class)->publicCatalogCounts();

        $this->assertSame($before + 1, $counts2['teacher']);
    }
}
