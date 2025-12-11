<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Place;

use Illuminate\Database\Eloquent\Model;
use App\Models\Place;
use App\MoonShine\Resources\Place\Pages\PlaceIndexPage;
use App\MoonShine\Resources\Place\Pages\PlaceFormPage;
use App\MoonShine\Resources\Place\Pages\PlaceDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Place, PlaceIndexPage, PlaceFormPage, PlaceDetailPage>
 */
class PlaceResource extends ModelResource
{
    protected string $model = Place::class;

    protected string $title = 'Places';
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            PlaceIndexPage::class,
            PlaceFormPage::class,
            PlaceDetailPage::class,
        ];
    }
}
