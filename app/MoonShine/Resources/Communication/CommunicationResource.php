<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Communication;

use Illuminate\Database\Eloquent\Model;
use App\Models\Communication;
use App\MoonShine\Resources\Communication\Pages\CommunicationIndexPage;
use App\MoonShine\Resources\Communication\Pages\CommunicationFormPage;
use App\MoonShine\Resources\Communication\Pages\CommunicationDetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Communication, CommunicationIndexPage, CommunicationFormPage, CommunicationDetailPage>
 */
#[Icon('users')]
#[Group('ui.moonshine.types.Tablename', 'users', translatable: true)]
#[Order(0)]
class CommunicationResource extends ModelResource
{
    protected string $model = Communication::class;

    public function getTitle(): string
    {
        return __('moonshine.communications.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            CommunicationIndexPage::class,
            CommunicationFormPage::class,
            CommunicationDetailPage::class,
        ];
    }
}
