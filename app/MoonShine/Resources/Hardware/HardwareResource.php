<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Hardware;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hardware;
use App\MoonShine\Resources\Hardware\Pages\HardwareIndexPage;
use App\MoonShine\Resources\Hardware\Pages\HardwareFormPage;
use App\MoonShine\Resources\Hardware\Pages\HardwareDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Hardware, HardwareIndexPage, HardwareFormPage, HardwareDetailPage>
 */
class HardwareResource extends ModelResource
{
    protected string $model = Hardware::class;

    public function getTitle(): string
    {
        return __('moonshine.hardware.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            HardwareIndexPage::class,
            HardwareFormPage::class,
            HardwareDetailPage::class,
        ];
    }
}
