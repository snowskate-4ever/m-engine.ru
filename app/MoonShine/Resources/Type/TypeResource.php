<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Type;

use Illuminate\Database\Eloquent\Model;
use App\Models\Type;
use App\MoonShine\Resources\Type\Pages\TypeIndexPage;
use App\MoonShine\Resources\Type\Pages\TypeFormPage;
use App\MoonShine\Resources\Type\Pages\TypeDetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * @extends ModelResource<Type, TypeIndexPage, TypeFormPage, TypeDetailPage>
 */
class TypeResource extends ModelResource
{
    protected string $model = Type::class;

    public function getTitle(): string
    {
        return __('moonshine.types.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            TypeIndexPage::class,
            TypeFormPage::class,
            TypeDetailPage::class,
        ];
    }
}
