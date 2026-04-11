<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Services\Music\MusicPublicSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecordLabelAndProducerCenterPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_record_label_renders_when_enabled(): void
    {
        $slug = 'label-'.uniqid('', true);
        $name = 'Test Label '.$slug;
        RecordLabel::query()->create([
            'name' => $name,
            'description' => 'Indie imprint',
            'owner_user_id' => null,
            'slug' => $slug,
            'public_page_enabled' => true,
        ]);

        $this->get(route('public.labels.show', ['slug' => $slug]))
            ->assertOk()
            ->assertSee($name)
            ->assertSee('Indie imprint', false);
    }

    public function test_public_record_label_hidden_stub_when_disabled(): void
    {
        $slug = 'hidden-label-'.uniqid('', true);
        RecordLabel::query()->create([
            'name' => 'Hidden Label',
            'description' => null,
            'owner_user_id' => null,
            'slug' => $slug,
            'public_page_enabled' => false,
        ]);

        $this->get(route('public.labels.show', ['slug' => $slug]))
            ->assertOk()
            ->assertSee(__('ui.public_profile.hidden_heading'));
    }

    public function test_public_producer_center_renders_when_enabled(): void
    {
        $slug = 'pc-'.uniqid('', true);
        $name = 'Producer Hub '.$slug;
        ProducerCenter::query()->create([
            'name' => $name,
            'description' => 'Rooms and engineers',
            'owner_user_id' => null,
            'slug' => $slug,
            'public_page_enabled' => true,
        ]);

        $this->get(route('public.producer-centers.show', ['slug' => $slug]))
            ->assertOk()
            ->assertSee($name)
            ->assertSee('Rooms and engineers', false);
    }

    public function test_public_producer_center_hidden_stub_when_disabled(): void
    {
        $slug = 'hidden-pc-'.uniqid('', true);
        ProducerCenter::query()->create([
            'name' => 'Hidden PC',
            'description' => null,
            'owner_user_id' => null,
            'slug' => $slug,
            'public_page_enabled' => false,
        ]);

        $this->get(route('public.producer-centers.show', ['slug' => $slug]))
            ->assertOk()
            ->assertSee(__('ui.public_profile.hidden_heading'));
    }

    public function test_search_finds_public_record_label_by_category(): void
    {
        $token = 'LblSrch'.uniqid();
        RecordLabel::query()->create([
            'name' => 'Label '.$token,
            'description' => 'About',
            'owner_user_id' => null,
            'slug' => 'lbl-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $search = app(MusicPublicSearchService::class);
        $rows = $search->search($token, 'record_label');

        $this->assertTrue($rows->contains(fn (array $r) => $r['type'] === 'record_label' && str_contains($r['name'], $token)));
    }

    public function test_search_finds_public_producer_center_by_category(): void
    {
        $token = 'PcSrch'.uniqid();
        ProducerCenter::query()->create([
            'name' => 'Center '.$token,
            'description' => 'Studio complex',
            'owner_user_id' => null,
            'slug' => 'pc-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $search = app(MusicPublicSearchService::class);
        $rows = $search->search($token, 'producer_center');

        $this->assertTrue($rows->contains(fn (array $r) => $r['type'] === 'producer_center' && str_contains($r['name'], $token)));
    }

    public function test_public_catalog_counts_include_new_types(): void
    {
        RecordLabel::query()->create([
            'name' => 'Cnt Label',
            'description' => null,
            'owner_user_id' => null,
            'slug' => 'cnt-lbl-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);
        ProducerCenter::query()->create([
            'name' => 'Cnt PC',
            'description' => null,
            'owner_user_id' => null,
            'slug' => 'cnt-pc-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        MusicPublicSearchService::forgetPublicCatalogCountsCache();
        $counts = app(MusicPublicSearchService::class)->publicCatalogCounts();

        $this->assertGreaterThanOrEqual(1, $counts['record_label']);
        $this->assertGreaterThanOrEqual(1, $counts['producer_center']);
    }
}
