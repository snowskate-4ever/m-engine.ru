<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Models\AiProvider;
use App\Support\AiChatDrivers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<AiProvider>
 */
final class AiProviderResource extends ModelResource
{
    protected string $model = AiProvider::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.providers_tab');
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
            Text::make(__('moonshine.ai.provider_name'), 'name'),
            Text::make(__('moonshine.ai.provider_driver'), 'driver'),
            Text::make(__('moonshine.ai.provider_scope'), 'scope'),
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
            Box::make(__('moonshine.ai.provider_box'), [
                ID::make()->disabled(),
                Text::make(__('moonshine.ai.provider_name'), 'name')
                    ->required(),
                Select::make(__('moonshine.ai.provider_driver'), 'driver')
                    ->options(AiChatDrivers::optionsForSelect())
                    ->required(),
                Select::make(__('moonshine.ai.provider_scope'), 'scope')
                    ->options([
                        'server' => 'server',
                        'template' => 'template',
                    ])
                    ->default('server')
                    ->required(),
                Json::make(__('moonshine.ai.provider_config'), 'config')
                    ->object()
                    ->fields([
                        Text::make(__('moonshine.ai.config_api_key'), 'api_key')
                            ->nullable()
                            ->hint(__('moonshine.ai.config_api_key_hint')),
                        Text::make(__('moonshine.ai.config_base_url'), 'base_url')
                            ->nullable()
                            ->hint(__('moonshine.ai.config_base_url_hint')),
                    ])
                    ->nullable(),
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
        return [
            Box::make(__('moonshine.ai.provider_box'), [
                ID::make(),
                Text::make(__('moonshine.ai.provider_name'), 'name'),
                Text::make(__('moonshine.ai.provider_driver'), 'driver'),
                Text::make(__('moonshine.ai.provider_scope'), 'scope'),
                Json::make(__('moonshine.ai.provider_config'), 'config')
                    ->object()
                    ->fields([
                        Text::make(__('moonshine.ai.config_api_key'), 'api_key')->nullable(),
                        Text::make(__('moonshine.ai.config_base_url'), 'base_url')->nullable(),
                    ])
                    ->nullable(),
                Switcher::make(__('moonshine.ai.is_active'), 'is_active'),
                Number::make(__('moonshine.ai.sort_order'), 'sort_order'),
                HasMany::make(__('moonshine.ai.server_models'), 'serverModels', resource: AiServerModelResource::class)
                    ->hideOnIndex(),
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

    public function search(): array
    {
        return ['id', 'name', 'driver'];
    }

    public function rules(Model $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', Rule::in(AiChatDrivers::keys())],
            'scope' => ['required', 'string', 'in:server,template'],
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
