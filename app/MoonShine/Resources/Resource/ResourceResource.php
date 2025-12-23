<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource;

use Illuminate\Database\Eloquent\Model;
use App\Models\Resource;
use App\MoonShine\Resources\Resource\Pages\ResourceIndexPage;
use App\MoonShine\Resources\Resource\Pages\ResourceFormPage;
use App\MoonShine\Resources\Resource\Pages\ResourceDetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Models, ResourceIndexPage, ResourceFormPage, ResourceDetailPage>
 */
class ResourceResource extends ModelResource
{
    protected string $model = Resource::class;

    public function getTitle(): string
    {
        return __('moonshine.resources.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            ResourceIndexPage::class,
            ResourceFormPage::class,
            ResourceDetailPage::class,
        ];
    }
}
