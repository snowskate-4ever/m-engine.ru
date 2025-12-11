<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Good;

use Illuminate\Database\Eloquent\Model;
use App\Models\Good;
use App\MoonShine\Resources\Good\Pages\GoodIndexPage;
use App\MoonShine\Resources\Good\Pages\GoodFormPage;
use App\MoonShine\Resources\Good\Pages\GoodDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Good, GoodIndexPage, GoodFormPage, GoodDetailPage>
 */
class GoodResource extends ModelResource
{
    protected string $model = Good::class;

    public function getTitle(): string
    {
        return __('moonshine.goods.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            GoodIndexPage::class,
            GoodFormPage::class,
            GoodDetailPage::class,
        ];
    }
}
