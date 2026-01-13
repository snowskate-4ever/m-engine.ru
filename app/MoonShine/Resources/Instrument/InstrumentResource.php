<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Instrument;

use Illuminate\Database\Eloquent\Model;
use App\Models\Instrument;
use App\MoonShine\Resources\Instrument\Pages\InstrumentIndexPage;
use App\MoonShine\Resources\Instrument\Pages\InstrumentFormPage;
use App\MoonShine\Resources\Instrument\Pages\InstrumentDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Instrument, InstrumentIndexPage, InstrumentFormPage, InstrumentDetailPage>
 */
class InstrumentResource extends ModelResource
{
    protected string $model = Instrument::class;

    public function getTitle(): string
    {
        return __('moonshine.instruments.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            InstrumentIndexPage::class,
            InstrumentFormPage::class,
            InstrumentDetailPage::class,
        ];
    }

    public function getEloquentQuery(): \Illuminate\Contracts\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc');
    }
}
