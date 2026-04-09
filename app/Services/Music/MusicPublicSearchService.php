<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class MusicPublicSearchService
{
    public const CATEGORY_ALL = 'all';

    private const PUBLIC_CATALOG_COUNTS_CACHE_KEY = 'music.public_catalog_counts.v1';

    private const PUBLIC_CATALOG_COUNTS_TTL_SECONDS = 120;

    /**
     * Категории для отдельных публичных URL /discover/{category} (без «all»).
     *
     * @return list<string>
     */
    public static function scopedDiscoverRouteCategories(): array
    {
        return array_values(array_filter(
            self::categories(),
            static fn (string $c) => $c !== self::CATEGORY_ALL,
        ));
    }

    public static function forgetPublicCatalogCountsCache(): void
    {
        Cache::forget(self::PUBLIC_CATALOG_COUNTS_CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_ALL,
            'musician',
            'teacher',
            'performer',
            'studio',
            'rehearsal',
            'school',
            'record_label',
            'producer_center',
            'shop',
        ];
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    public function search(string $term, string $category = self::CATEGORY_ALL): Collection
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        if (! in_array($category, self::categories(), true)) {
            $category = self::CATEGORY_ALL;
        }

        $like = '%'.$this->escapeLike($term).'%';
        $perType = $category === self::CATEGORY_ALL ? 12 : 50;

        $rows = collect();

        if ($category === self::CATEGORY_ALL || $category === 'musician') {
            $rows = $rows->merge($this->searchMusicians($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'teacher') {
            $rows = $rows->merge($this->searchTeachers($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'performer') {
            $rows = $rows->merge($this->searchPerformers($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'studio') {
            $rows = $rows->merge($this->searchStudios($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'rehearsal') {
            $rows = $rows->merge($this->searchRehearsals($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'school') {
            $rows = $rows->merge($this->searchSchools($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'record_label') {
            $rows = $rows->merge($this->searchRecordLabels($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'producer_center') {
            $rows = $rows->merge($this->searchProducerCenters($like, $perType));
        }
        if ($category === self::CATEGORY_ALL || $category === 'shop') {
            $rows = $rows->merge($this->searchShops($like, $perType));
        }

        return $rows
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchMusicians(string $like, int $limit): Collection
    {
        $q = $this->musicianPublic();
        $this->applyTextMatch($q, $like, true);

        return $this->mapMusicians($q->orderBy('name')->limit($limit)->get());
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchTeachers(string $like, int $limit): Collection
    {
        $q = $this->teacherPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'teacher', 'public.teachers.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchPerformers(string $like, int $limit): Collection
    {
        $q = $this->peformerPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'performer', 'public.performers.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchStudios(string $like, int $limit): Collection
    {
        $q = $this->studioPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'studio', 'public.studios.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchRehearsals(string $like, int $limit): Collection
    {
        $q = $this->rehearsalPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'rehearsal', 'public.rehearsals.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchSchools(string $like, int $limit): Collection
    {
        $q = $this->schoolPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'school', 'public.schools.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchRecordLabels(string $like, int $limit): Collection
    {
        $q = $this->recordLabelPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'record_label', 'public.labels.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchProducerCenters(string $like, int $limit): Collection
    {
        $q = $this->producerCenterPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'producer_center', 'public.producer-centers.show');
    }

    /**
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function searchShops(string $like, int $limit): Collection
    {
        $q = $this->shopPublic();
        $this->applyTextMatch($q, $like, false);

        return $this->mapSimple($q->orderBy('name')->limit($limit)->get(), 'shop', 'public.shops.show');
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }

    private function musicianPublic(): Builder
    {
        return Musician::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function teacherPublic(): Builder
    {
        return Teacher::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function peformerPublic(): Builder
    {
        return Peformer::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function studioPublic(): Builder
    {
        return Studio::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function rehearsalPublic(): Builder
    {
        return Rehersal::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function schoolPublic(): Builder
    {
        return School::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function recordLabelPublic(): Builder
    {
        return RecordLabel::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function producerCenterPublic(): Builder
    {
        return ProducerCenter::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    private function shopPublic(): Builder
    {
        return Shop::query()
            ->where('public_page_enabled', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '');
    }

    /**
     * Количество профилей, участвующих в публичном поиске по каталогу (включена публичная страница и slug).
     *
     * @return array{musician: int, teacher: int, performer: int, studio: int, rehearsal: int, school: int, record_label: int, producer_center: int, shop: int}
     */
    public function publicCatalogCounts(): array
    {
        return Cache::remember(
            self::PUBLIC_CATALOG_COUNTS_CACHE_KEY,
            self::PUBLIC_CATALOG_COUNTS_TTL_SECONDS,
            fn (): array => [
                'musician' => $this->musicianPublic()->count(),
                'teacher' => $this->teacherPublic()->count(),
                'performer' => $this->peformerPublic()->count(),
                'studio' => $this->studioPublic()->count(),
                'rehearsal' => $this->rehearsalPublic()->count(),
                'school' => $this->schoolPublic()->count(),
                'record_label' => $this->recordLabelPublic()->count(),
                'producer_center' => $this->producerCenterPublic()->count(),
                'shop' => $this->shopPublic()->count(),
            ],
        );
    }

    private function applyTextMatch(Builder $query, string $like, bool $includeBio): void
    {
        $query->where(function (Builder $q) use ($like, $includeBio) {
            $q->where('name', 'like', $like)
                ->orWhere('description', 'like', $like);
            if ($includeBio) {
                $q->orWhere('bio', 'like', $like);
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Musician>  $musicians
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function mapMusicians($musicians): Collection
    {
        return $musicians->map(fn (Musician $m) => [
            'type' => 'musician',
            'name' => $m->name,
            'url' => route('public.musicians.show', ['slug' => $m->slug]),
            'excerpt' => $this->excerpt((string) ($m->bio ?? $m->description ?? '')),
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Teacher|Peformer|Studio|Rehersal|School|RecordLabel|ProducerCenter|Shop>  $items
     * @return Collection<int, array{type: string, name: string, url: string, excerpt: ?string}>
     */
    private function mapSimple($items, string $type, string $routeName): Collection
    {
        return $items->map(fn ($row) => [
            'type' => $type,
            'name' => $row->name,
            'url' => route($routeName, ['slug' => $row->slug]),
            'excerpt' => $this->excerpt((string) ($row->description ?? '')),
        ]);
    }

    private function excerpt(string $htmlOrText): ?string
    {
        $plain = trim(html_entity_decode(strip_tags($htmlOrText), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $plain === '' ? null : Str::limit($plain, 160);
    }
}
