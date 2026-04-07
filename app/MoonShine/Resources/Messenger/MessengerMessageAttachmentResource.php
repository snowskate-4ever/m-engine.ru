<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Messenger;

use App\Models\MessageAttachment;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<MessageAttachment>
 */
class MessengerMessageAttachmentResource extends ModelResource
{
    protected string $model = MessageAttachment::class;

    public function getTitle(): string
    {
        return __('moonshine.messenger.attachments_tab');
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make(__('moonshine.messenger.disk'), 'disk'),
            Text::make(__('moonshine.messenger.path'), 'path'),
            Text::make(__('moonshine.messenger.original_name'), 'original_name'),
            Text::make(__('moonshine.messenger.mime'), 'mime'),
            Number::make(__('moonshine.messenger.size'), 'size'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return $this->detailFields();
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make(__('moonshine.messenger.attachment_box'), [
                ID::make(),
                Text::make(__('moonshine.messenger.disk'), 'disk'),
                Text::make(__('moonshine.messenger.path'), 'path'),
                Text::make(__('moonshine.messenger.original_name'), 'original_name'),
                Text::make(__('moonshine.messenger.mime'), 'mime'),
                Number::make(__('moonshine.messenger.size'), 'size'),
                Text::make(__('moonshine.messenger.checksum'), 'checksum'),
                Text::make(__('moonshine.messenger.created_at'), 'created_at'),
            ]),
        ];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\Crud\IndexPage::class,
            \MoonShine\Laravel\Pages\Crud\FormPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function search(): array
    {
        return ['path', 'original_name', 'mime'];
    }
}
