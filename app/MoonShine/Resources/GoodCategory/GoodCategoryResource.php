<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\GoodCategory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Good;
use App\MoonShine\Resources\GoodCategory\Pages\GoodCategoryIndexPage;
use App\MoonShine\Resources\GoodCategory\Pages\GoodCategoryFormPage;
use App\MoonShine\Resources\GoodCategory\Pages\GoodCategoryDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<GoodCategory, GoodCategoryIndexPage, GoodCategoryFormPage, GoodCategoryDetailPage>
 */
class GoodCategoryResource extends ModelResource
{
    protected string $model = Good::class;

    public function getTitle(): string
    {
        return __('moonshine.goodcategory.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            GoodCategoryIndexPage::class,
            GoodCategoryFormPage::class,
            GoodCategoryDetailPage::class,
        ];
    }
}
