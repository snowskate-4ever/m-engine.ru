<?php

declare(strict_types=1);

namespace App\Services\Music;

use App\Models\Musician;
use App\Models\Peformer;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Studio;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class MusicPublicSearchService
{
    public const CATEGORY_ALL = 'all';

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
     * @param  \Illuminate\Database\Eloquent\Collection<int, Teacher|Peformer|Studio|Rehersal|School>  $items
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
