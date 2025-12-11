<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Event;
use App\MoonShine\Resources\Event\Pages\EventIndexPage;
use App\MoonShine\Resources\Event\Pages\EventFormPage;
use App\MoonShine\Resources\Event\Pages\EventDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Event, EventIndexPage, EventFormPage, EventDetailPage>
 */
#[Icon('events')]
#[Group('moonshine.events.Tablename', 'events', translatable: true)]
#[Order(0)]
class EventResource extends ModelResource
{
    protected string $model = Event::class;

    public function getTitle(): string
    {
        return __('moonshine.events.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            EventIndexPage::class,
            EventFormPage::class,
            EventDetailPage::class,
        ];
    }
}
