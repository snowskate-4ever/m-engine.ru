<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Enums\UserAiSubscriptionStatus;
use App\Models\AiSubscriptionTier;
use App\Models\User;
use App\Models\UserAiSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<UserAiSubscription>
 */
final class UserAiSubscriptionResource extends ModelResource
{
    protected string $model = UserAiSubscription::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.user_subscriptions_tab');
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
            BelongsTo::make(__('moonshine.ai.user'), 'user', formatted: static fn (User $u) => $u->name.' #'.$u->id)
                ->searchable(),
            BelongsTo::make(__('moonshine.ai.subscription_tier'), 'tier', formatted: static fn (AiSubscriptionTier $t) => $t->name)
                ->searchable(),
            Enum::make(__('moonshine.ai.subscription_status'), 'status')->attach(UserAiSubscriptionStatus::class),
            Date::make(__('moonshine.ai.period_end'), 'current_period_end')->withTime(),
            Text::make(__('moonshine.ai.payment_provider'), 'payment_provider'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make(__('moonshine.ai.user_subscription_box'), [
                ID::make()->disabled(),
                BelongsTo::make(__('moonshine.ai.user'), 'user', formatted: static fn (User $u) => $u->name.' #'.$u->id)
                    ->searchable()
                    ->required(),
                BelongsTo::make(__('moonshine.ai.subscription_tier'), 'tier', formatted: static fn (AiSubscriptionTier $t) => $t->name)
                    ->searchable()
                    ->required(),
                Enum::make(__('moonshine.ai.subscription_status'), 'status')
                    ->attach(UserAiSubscriptionStatus::class)
                    ->required(),
                Date::make(__('moonshine.ai.period_start'), 'current_period_start')->withTime()->nullable(),
                Date::make(__('moonshine.ai.period_end'), 'current_period_end')->withTime()->required(),
                Text::make(__('moonshine.ai.payment_provider'), 'payment_provider')->nullable(),
                Text::make(__('moonshine.ai.external_payment_ref'), 'external_payment_ref')->nullable(),
                Switcher::make(__('moonshine.ai.cancel_at_period_end'), 'cancel_at_period_end')->default(false),
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
            \MoonShine\Laravel\Pages\IndexPage::class,
            \MoonShine\Laravel\Pages\FormPage::class,
            \MoonShine\Laravel\Pages\DetailPage::class,
        ];
    }

    public function search(): array
    {
        return ['id', 'payment_provider', 'external_payment_ref'];
    }

    public function rules(Model $item): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'ai_subscription_tier_id' => ['required', 'integer', Rule::exists('ai_subscription_tiers', 'id')],
            'status' => ['required', Rule::enum(UserAiSubscriptionStatus::class)],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['required', 'date'],
            'payment_provider' => ['nullable', 'string', 'max:64'],
            'external_payment_ref' => ['nullable', 'string', 'max:191'],
            'cancel_at_period_end' => ['boolean'],
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
