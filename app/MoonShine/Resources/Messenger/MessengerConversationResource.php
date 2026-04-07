<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Messenger;

use App\Enums\ConversationType;
use App\Events\Messenger\ConversationRetentionUpdated;
use App\Models\Conversation;
use App\Models\User;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<Conversation>
 */
class MessengerConversationResource extends ModelResource
{
    protected string $model = Conversation::class;

    public function getTitle(): string
    {
        return __('moonshine.messenger.conversations_tab');
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
            Enum::make(__('moonshine.messenger.conversation_type'), 'type')
                ->attach(ConversationType::class),
            Text::make(__('moonshine.messenger.title'), 'title')
                ->nullable(),
            Number::make(__('moonshine.messenger.retention_days'), 'retention_days')
                ->nullable(),
            BelongsTo::make(
                __('moonshine.messenger.creator'),
                'creator',
                formatted: static fn (?User $model) => $model?->name ?? '—',
                resource: UserResource::class,
            )
                ->nullable()
                ->searchable(),
            Date::make(__('moonshine.messenger.created_at'), 'created_at')->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make(__('moonshine.messenger.conversation_edit_box'), [
                ID::make()->disabled(),
                Enum::make(__('moonshine.messenger.conversation_type'), 'type')
                    ->attach(ConversationType::class)
                    ->disabled(),
                Text::make(__('moonshine.messenger.title'), 'title')
                    ->disabled()
                    ->nullable(),
                BelongsTo::make(
                    __('moonshine.messenger.creator'),
                    'creator',
                    formatted: static fn (?User $model) => $model?->name ?? '—',
                    resource: UserResource::class,
                )
                    ->disabled()
                    ->nullable(),
                Number::make(__('moonshine.messenger.retention_days'), 'retention_days')
                    ->nullable()
                    ->min(1)
                    ->max(65_535)
                    ->hint(__('moonshine.messenger.retention_hint')),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make(__('moonshine.messenger.conversation_box'), [
                ID::make(),
                Enum::make(__('moonshine.messenger.conversation_type'), 'type')
                    ->attach(ConversationType::class),
                Text::make(__('moonshine.messenger.title'), 'title')
                    ->nullable(),
                Number::make(__('moonshine.messenger.retention_days'), 'retention_days')
                    ->nullable(),
                BelongsTo::make(
                    __('moonshine.messenger.creator'),
                    'creator',
                    formatted: static fn (?User $model) => $model?->name ?? '—',
                    resource: UserResource::class,
                )
                    ->nullable(),
                Text::make(__('moonshine.messenger.direct_peer_min_id'), 'direct_peer_min_id')
                    ->nullable(),
                Text::make(__('moonshine.messenger.direct_peer_max_id'), 'direct_peer_max_id')
                    ->nullable(),
                Text::make(__('moonshine.messenger.ai_server_model_id'), 'ai_server_model_id')
                    ->nullable(),
                Text::make(__('moonshine.messenger.user_ai_connection_id'), 'user_ai_connection_id')
                    ->nullable(),
                HasMany::make(
                    __('moonshine.messenger.participants'),
                    'conversationUsers',
                    resource: MessengerConversationUserResource::class,
                )->hideOnIndex(),
                HasMany::make(
                    __('moonshine.messenger.messages'),
                    'messages',
                    resource: MessengerMessageResource::class,
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
            \MoonShine\Laravel\Pages\Crud\IndexPage::class,
            \MoonShine\Laravel\Pages\Crud\FormPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function getActiveActions(): array
    {
        return ['view', 'update'];
    }

    public function search(): array
    {
        return ['id', 'title'];
    }

    public function filters(): array
    {
        return [
            Enum::make(__('moonshine.messenger.conversation_type'), 'type')
                ->attach(ConversationType::class),
        ];
    }

    public function rules(Model $item): array
    {
        return [
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @param  DataWrapperContract<Conversation>  $item
     * @return DataWrapperContract<Conversation>
     */
    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        $conversation = $item->getOriginal();
        if ($conversation instanceof Conversation && $conversation->wasChanged('retention_days')) {
            $user = MoonShineAuth::getGuard()->user();
            if ($user instanceof User) {
                $notifyPeer = null;
                if ($conversation->type === ConversationType::Direct) {
                    if ($conversation->direct_peer_min_id === $user->id) {
                        $notifyPeer = $conversation->direct_peer_max_id;
                    } elseif ($conversation->direct_peer_max_id === $user->id) {
                        $notifyPeer = $conversation->direct_peer_min_id;
                    }
                }
                event(new ConversationRetentionUpdated(
                    $conversation,
                    $user,
                    $notifyPeer,
                    $conversation->retention_days,
                ));
            }
        }

        return parent::afterUpdated($item);
    }
}
