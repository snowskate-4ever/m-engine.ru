<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Room;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use App\MoonShine\Resources\Room\Pages\RoomIndexPage;
use App\MoonShine\Resources\Room\Pages\RoomFormPage;
use App\MoonShine\Resources\Room\Pages\RoomDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;

/**
 * @extends ModelResource<Room, RoomIndexPage, RoomFormPage, RoomDetailPage>
 */
class RoomResource extends ModelResource
{
    protected string $model = Room::class;

    public function getTitle(): string
    {
        return __('moonshine.rooms.Tablename');
    }
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            RoomIndexPage::class,
            RoomFormPage::class,
            RoomDetailPage::class,
        ];
    }
}
