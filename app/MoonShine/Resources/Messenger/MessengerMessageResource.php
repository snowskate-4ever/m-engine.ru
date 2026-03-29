<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Messenger;

use App\Enums\MessageKind;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Message>
 */
class MessengerMessageResource extends ModelResource
{
    protected string $model = Message::class;

    public function getTitle(): string
    {
        return __('moonshine.messenger.messages_tab');
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc($builder->getModel()->getQualifiedKeyName());
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            BelongsTo::make(
                __('moonshine.messenger.conversation'),
                'conversation',
                formatted: static fn (Conversation $model) => '#'.$model->id
                    .($model->title !== null && $model->title !== '' ? ' — '.$model->title : ''),
            )
                ->searchable(),
            BelongsTo::make(
                __('moonshine.messenger.author'),
                'user',
                formatted: static fn (User $model) => $model->name,
            )
                ->searchable(),
            Enum::make(__('moonshine.messenger.message_kind'), 'kind')
                ->attach(MessageKind::class),
            Text::make(__('moonshine.messenger.body_preview'), 'body')
                ->unescape(),
            Switcher::make(__('moonshine.messenger.is_forward'), 'is_forward'),
            Date::make(__('moonshine.messenger.created_at'), 'created_at')->format('d.m.Y H:i'),
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
            Box::make(__('moonshine.messenger.message_box'), [
                ID::make(),
                BelongsTo::make(
                    __('moonshine.messenger.conversation'),
                    'conversation',
                    formatted: static fn (Conversation $model) => '#'.$model->id
                        .($model->title !== null && $model->title !== '' ? ' — '.$model->title : ''),
                ),
                BelongsTo::make(
                    __('moonshine.messenger.author'),
                    'user',
                    formatted: static fn (User $model) => $model->name,
                ),
                Enum::make(__('moonshine.messenger.message_kind'), 'kind')
                    ->attach(MessageKind::class),
                Textarea::make(__('moonshine.messenger.body'), 'body')
                    ->unescape(),
                Switcher::make(__('moonshine.messenger.is_forward'), 'is_forward'),
                BelongsTo::make(
                    __('moonshine.messenger.forwarded_from'),
                    'forwardedFrom',
                    formatted: static fn (?Message $model) => $model ? (string) $model->getKey() : '—',
                )
                    ->nullable(),
                Json::make(__('moonshine.messenger.forward_snapshot'), 'forward_snapshot')
                    ->nullable(),
                Text::make(__('moonshine.messenger.client_message_id'), 'client_message_id')
                    ->nullable(),
                HasMany::make(
                    __('moonshine.messenger.attachments'),
                    'attachments',
                    resource: MessengerMessageAttachmentResource::class,
                )->hideOnIndex(),
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
        return ['id', 'body', 'client_message_id'];
    }

    public function filters(): array
    {
        return [
            Enum::make(__('moonshine.messenger.message_kind'), 'kind')
                ->attach(MessageKind::class),
        ];
    }
}
