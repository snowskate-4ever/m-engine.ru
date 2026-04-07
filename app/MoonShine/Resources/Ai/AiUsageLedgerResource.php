<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Enums\AiRequestSource;
use App\Models\AiServerModel;
use App\Models\AiUsageLedger;
use App\Models\User;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<AiUsageLedger>
 */
final class AiUsageLedgerResource extends ModelResource
{
    protected string $model = AiUsageLedger::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.usage_ledger_tab');
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc('created_at')->orderByDesc($builder->getModel()->getQualifiedKeyName());
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Date::make(__('moonshine.ai.created_at'), 'created_at')->format('d.m.Y H:i'),
            BelongsTo::make(
                __('moonshine.ai.user'),
                'user',
                formatted: static fn (User $u) => $u->email ?? (string) $u->getKey(),
                resource: UserResource::class,
            )->searchable(),
            BelongsTo::make(
                __('moonshine.ai.ai_server_model_id'),
                'serverModel',
                formatted: static fn (AiServerModel $m) => $m->display_name,
                resource: AiServerModelResource::class,
            )->nullable()->searchable(),
            Enum::make(__('moonshine.ai.source'), 'source')->attach(AiRequestSource::class),
            Number::make(__('moonshine.ai.tokens_prompt'), 'tokens_prompt'),
            Number::make(__('moonshine.ai.tokens_completion'), 'tokens_completion'),
            Text::make(__('moonshine.ai.estimated_internal_cost'), 'estimated_internal_cost')->nullable(),
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
            Box::make(__('moonshine.ai.usage_ledger_box'), [
                ID::make(),
                Date::make(__('moonshine.ai.created_at'), 'created_at'),
                BelongsTo::make(
                    __('moonshine.ai.user'),
                    'user',
                    formatted: static fn (User $u) => $u->email ?? (string) $u->getKey(),
                    resource: UserResource::class,
                ),
                BelongsTo::make(
                    __('moonshine.ai.ai_server_model_id'),
                    'serverModel',
                    formatted: static fn (AiServerModel $m) => $m->display_name,
                    resource: AiServerModelResource::class,
                )->nullable(),
                Text::make(__('moonshine.ai.conversation_id'), 'conversation_id')->nullable(),
                Enum::make(__('moonshine.ai.source'), 'source')->attach(AiRequestSource::class),
                Number::make(__('moonshine.ai.tokens_prompt'), 'tokens_prompt'),
                Number::make(__('moonshine.ai.tokens_completion'), 'tokens_completion'),
                Text::make(__('moonshine.ai.estimated_internal_cost'), 'estimated_internal_cost')->nullable(),
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
        return ['id'];
    }

    public function permissions(): array
    {
        $u = auth()->user();

        return [
            'view' => $u && ($u->hasRole('admin') || $u->hasRole('editor')),
            'create' => false,
            'update' => false,
            'delete' => false,
            'massDelete' => false,
        ];
    }
}
