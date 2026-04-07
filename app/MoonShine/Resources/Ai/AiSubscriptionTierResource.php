<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Models\AiSubscriptionTier;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<AiSubscriptionTier>
 */
final class AiSubscriptionTierResource extends ModelResource
{
    protected string $model = AiSubscriptionTier::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.subscription_tiers_tab');
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
            Text::make(__('moonshine.ai.tier_slug'), 'slug'),
            Text::make(__('moonshine.ai.tier_name'), 'name'),
            Text::make(__('moonshine.ai.price_monthly_rub'), 'price_monthly_rub'),
            Text::make(__('moonshine.ai.discount_percent'), 'discount_percent')->nullable(),
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
            Box::make(__('moonshine.ai.subscription_tier_box'), [
                ID::make()->disabled(),
                Text::make(__('moonshine.ai.tier_slug'), 'slug')
                    ->required()
                    ->hint(__('moonshine.ai.tier_slug_hint')),
                Text::make(__('moonshine.ai.tier_name'), 'name')->required(),
                Textarea::make(__('moonshine.ai.tier_description'), 'description')->nullable(),
                Text::make(__('moonshine.ai.price_monthly_rub'), 'price_monthly_rub')->nullable(),
                Text::make(__('moonshine.ai.discount_percent'), 'discount_percent')->nullable(),
                Text::make(__('moonshine.ai.discount_amount_fixed'), 'discount_amount_fixed')->nullable(),
                Date::make(__('moonshine.ai.discount_valid_until'), 'discount_valid_until')->withTime()->nullable(),
                Text::make(__('moonshine.ai.internal_reference_cost_monthly'), 'internal_reference_cost_monthly')->nullable(),
                Json::make(__('moonshine.ai.tier_limits_json'), 'limits')
                    ->nullable()
                    ->hint(__('moonshine.ai.tier_limits_hint')),
                Switcher::make(__('moonshine.ai.is_active'), 'is_active')->default(true),
                Number::make(__('moonshine.ai.sort_order'), 'sort_order')->default(0)->min(0),
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
        return ['id', 'slug', 'name'];
    }

    public function rules(Model $item): array
    {
        return [
            'slug' => ['required', 'string', 'max:64', Rule::unique('ai_subscription_tiers', 'slug')->ignore($item->getKey())],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_monthly_rub' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount_fixed' => ['nullable', 'numeric', 'min:0'],
            'discount_valid_until' => ['nullable', 'date'],
            'internal_reference_cost_monthly' => ['nullable', 'numeric', 'min:0'],
            'limits' => ['nullable', 'array'],
            'is_active' => ['boolean'],
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
