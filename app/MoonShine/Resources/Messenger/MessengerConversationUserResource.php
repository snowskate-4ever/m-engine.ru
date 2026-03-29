<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Messenger;

use App\Enums\ConversationRole;
use App\Models\ConversationUser;
use App\Models\User;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;

/**
 * @extends ModelResource<ConversationUser>
 */
class MessengerConversationUserResource extends ModelResource
{
    protected string $model = ConversationUser::class;

    public function getTitle(): string
    {
        return __('moonshine.messenger.participants_tab');
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            BelongsTo::make(
                __('moonshine.messenger.user'),
                'user',
                formatted: static fn (User $model) => $model->name,
            )
                ->searchable(),
            Enum::make(__('moonshine.messenger.role'), 'role')
                ->attach(ConversationRole::class),
            Date::make(__('moonshine.messenger.joined_at'), 'joined_at')->format('d.m.Y H:i'),
            Switcher::make(__('moonshine.messenger.notifications_muted'), 'notifications_muted'),
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
            Box::make(__('moonshine.messenger.participant_box'), [
                ID::make(),
                BelongsTo::make(
                    __('moonshine.messenger.user'),
                    'user',
                    formatted: static fn (User $model) => $model->name,
                ),
                Enum::make(__('moonshine.messenger.role'), 'role')
                    ->attach(ConversationRole::class),
                Number::make(__('moonshine.messenger.last_read_message_id'), 'last_read_message_id')
                    ->nullable(),
                Date::make(__('moonshine.messenger.joined_at'), 'joined_at'),
                Switcher::make(__('moonshine.messenger.notifications_muted'), 'notifications_muted'),
                Date::make(__('moonshine.messenger.mute_until'), 'mute_until')
                    ->format('d.m.Y H:i')
                    ->nullable(),
                Date::make(__('moonshine.messenger.created_at'), 'created_at'),
                Date::make(__('moonshine.messenger.updated_at'), 'updated_at'),
            ]),
        ];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\IndexPage::class,
            \MoonShine\Laravel\Pages\FormPage::class,
            \MoonShine\Laravel\Pages\DetailPage::class,
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function search(): array
    {
        return ['id'];
    }
}
