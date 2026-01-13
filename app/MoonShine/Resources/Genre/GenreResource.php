<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Genre;

use Illuminate\Database\Eloquent\Model;
use App\Models\Genre;
use App\MoonShine\Resources\Genre\Pages\GenreIndexPage;
use App\MoonShine\Resources\Genre\Pages\GenreFormPage;
use App\MoonShine\Resources\Genre\Pages\GenreDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Genre, GenreIndexPage, GenreFormPage, GenreDetailPage>
 */
class GenreResource extends ModelResource
{
    protected string $model = Genre::class;

    public function getTitle(): string
    {
        return __('moonshine.genres.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            GenreIndexPage::class,
            GenreFormPage::class,
            GenreDetailPage::class,
        ];
    }

    public function getEloquentQuery(): \Illuminate\Contracts\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }
}
