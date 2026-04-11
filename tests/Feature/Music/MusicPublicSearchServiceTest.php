<?php

declare(strict_types=1);

namespace Tests\Feature\Music;

use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Teacher;
use App\Services\Music\MusicPublicSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MusicPublicSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    private MusicPublicSearchService $search;

    protected function setUp(): void
    {
        parent::setUp();
        $this->search = app(MusicPublicSearchService::class);
    }

    public function test_empty_term_returns_no_results(): void
    {
        Musician::query()->create([
            'name' => 'Visible Artist',
            'description' => 'Desc',
            'slug' => 'visible-artist',
            'public_page_enabled' => true,
        ]);

        $this->assertCount(0, $this->search->search('', MusicPublicSearchService::CATEGORY_ALL));
        $this->assertCount(0, $this->search->search(' ', MusicPublicSearchService::CATEGORY_ALL));
    }

    public function test_term_shorter_than_two_chars_returns_no_results(): void
    {
        Musician::query()->create([
            'name' => 'Abba Cover Band',
            'description' => 'Desc',
            'slug' => 'abba-cover',
            'public_page_enabled' => true,
        ]);

        $this->assertCount(0, $this->search->search('a', MusicPublicSearchService::CATEGORY_ALL));
    }

    public function test_finds_public_profile_by_name(): void
    {
        $token = 'TokEnXyZ'.uniqid();
        Musician::query()->create([
            'name' => 'Artist '.$token,
            'description' => 'Nothing',
            'slug' => 'artist-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $results = $this->search->search(substr($token, 0, 8), MusicPublicSearchService::CATEGORY_ALL);
        $this->assertTrue($results->contains(fn (array $r) => $r['type'] === 'musician' && str_contains($r['name'], $token)));
    }

    public function test_excludes_profiles_without_public_enabled_or_slug(): void
    {
        $token = 'SecRet99'.uniqid();

        Musician::query()->create([
            'name' => 'Hidden '.$token.' A',
            'description' => 'Desc',
            'slug' => 'hidden-a-'.uniqid('', true),
            'public_page_enabled' => false,
        ]);

        Musician::query()->create([
            'name' => 'Hidden '.$token.' B',
            'description' => 'Desc',
            'slug' => null,
            'public_page_enabled' => true,
        ]);

        Musician::query()->create([
            'name' => 'Public '.$token,
            'description' => 'Desc',
            'slug' => 'public-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $results = $this->search->search($token, MusicPublicSearchService::CATEGORY_ALL);
        $this->assertCount(1, $results);
        $this->assertStringContainsString('Public', $results->first()['name']);
    }

    public function test_category_teacher_excludes_musician_hits(): void
    {
        $token = 'CatFilter'.uniqid();

        Musician::query()->create([
            'name' => 'Musician '.$token,
            'description' => 'Bio',
            'bio' => null,
            'slug' => 'm-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        Teacher::query()->create([
            'name' => 'Teacher '.$token,
            'description' => 'Teaches well',
            'user_id' => null,
            'slug' => 't-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $teacherOnly = $this->search->search($token, 'teacher');
        $this->assertTrue($teacherOnly->every(fn (array $r) => $r['type'] === 'teacher'));
        $this->assertCount(1, $teacherOnly);

        $performerOnly = $this->search->search($token, 'performer');
        $this->assertCount(0, $performerOnly);
    }

    public function test_category_performer_lists_public_band(): void
    {
        $token = 'BandFind'.uniqid();
        Peformer::query()->create([
            'name' => 'Group '.$token,
            'description' => 'We play',
            'owner_user_id' => null,
            'slug' => 'g-'.uniqid('', true),
            'public_page_enabled' => true,
        ]);

        $results = $this->search->search($token, 'performer');
        $this->assertCount(1, $results);
        $this->assertSame('performer', $results->first()['type']);
    }
}
