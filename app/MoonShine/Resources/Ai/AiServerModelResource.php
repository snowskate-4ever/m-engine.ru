<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Models\AiProvider;
use App\Models\AiServerModel;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<AiServerModel>
 */
final class AiServerModelResource extends ModelResource
{
    protected string $model = AiServerModel::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.server_models_tab');
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderBy('sort_order')->orderBy($builder->getModel()->getQualifiedKeyName());
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            BelongsTo::make(
                __('moonshine.ai.provider'),
                'provider',
                formatted: static fn (AiProvider $p) => $p->name,
                resource: AiProviderResource::class,
            )->searchable(),
            Text::make(__('moonshine.ai.vendor_model_id'), 'vendor_model_id'),
            Text::make(__('moonshine.ai.display_name'), 'display_name'),
            Switcher::make(__('moonshine.ai.is_active'), 'is_active'),
            Number::make(__('moonshine.ai.sort_order'), 'sort_order'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make(__('moonshine.ai.server_model_box'), [
                ID::make()->disabled(),
                BelongsTo::make(
                    __('moonshine.ai.provider'),
                    'provider',
                    formatted: static fn (AiProvider $p) => $p->name,
                    resource: AiProviderResource::class,
                )
                    ->searchable()
                    ->required(),
                Text::make(__('moonshine.ai.vendor_model_id'), 'vendor_model_id')
                    ->required()
                    ->hint(__('moonshine.ai.vendor_model_id_hint')),
                Text::make(__('moonshine.ai.display_name'), 'display_name')
                    ->required(),
                Text::make(__('moonshine.ai.cost_prompt_1k'), 'internal_cost_per_1k_prompt_tokens')
                    ->nullable()
                    ->hint(__('moonshine.ai.cost_hint')),
                Text::make(__('moonshine.ai.cost_completion_1k'), 'internal_cost_per_1k_completion_tokens')
                    ->nullable()
                    ->hint(__('moonshine.ai.cost_hint')),
                Text::make(__('moonshine.ai.cost_per_request'), 'estimated_cost_per_request')
                    ->nullable()
                    ->hint(__('moonshine.ai.cost_hint')),
                Switcher::make(__('moonshine.ai.is_active'), 'is_active')
                    ->default(true),
                Number::make(__('moonshine.ai.sort_order'), 'sort_order')
                    ->default(0)
                    ->min(0),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return $this->formFields();
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

    public function search(): array
    {
        return ['id', 'vendor_model_id', 'display_name'];
    }

    public function rules(Model $item): array
    {
        return [
            'ai_provider_id' => ['required', 'integer', Rule::exists('ai_providers', 'id')],
            'vendor_model_id' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'max:255'],
            'internal_cost_per_1k_prompt_tokens' => ['nullable', 'numeric'],
            'internal_cost_per_1k_completion_tokens' => ['nullable', 'numeric'],
            'estimated_cost_per_request' => ['nullable', 'numeric'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    public function permissions(): array
    {
        $u = auth()->user();

        return [
            'view' => $u && ($u->hasRole('admin') || $u->hasRole('editor')),
            'create' => $u && $u->hasRole('admin'),
            'update' => $u && $u->hasRole('admin'),
            'delete' => $u && $u->hasRole('admin'),
            'massDelete' => $u && $u->hasRole('admin'),
        ];
    }
}
