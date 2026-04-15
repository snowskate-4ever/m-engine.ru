<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Models\MatchingControlSetting;
use App\MoonShine\Resources\MusicEcosystem\Pages\MatchingControlSettingIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<MatchingControlSetting>
 */
#[Icon('cog-6-tooth')]
#[Group('Music', 'music')]
final class MatchingControlSettingResource extends ModelResource
{
    protected string $model = MatchingControlSetting::class;

    public function getTitle(): string
    {
        return 'Matching Control';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->latest('id');
    }

    protected function indexFields(): array
    {
        return [
            ID::make(),
            Switcher::make('Enabled', 'is_enabled'),
            Number::make('Interval (minutes)', 'interval_minutes'),
            Select::make('Default Scope', 'default_scope')
                ->options([
                    'all' => 'all',
                    'profiles' => 'profiles',
                    'entities' => 'entities',
                ]),
            Text::make('Provider', 'provider'),
            Text::make('Model', 'model'),
            Number::make('Threshold', 'score_threshold'),
        ];
    }

    protected function formFields(): array
    {
        return [
            Box::make('Matching Control Settings', [
                ID::make()->disabled(),
                Switcher::make('Enabled', 'is_enabled')->default(true),
                Number::make('Interval (minutes)', 'interval_minutes')->default(60)->min(1),
                Select::make('Default Scope', 'default_scope')
                    ->options([
                        'all' => 'all',
                        'profiles' => 'profiles',
                        'entities' => 'entities',
                    ])->default('all'),
                Text::make('Provider', 'provider')->required(),
                Text::make('Model', 'model')->required(),
                Number::make('Score Threshold', 'score_threshold')
                    ->step(0.01)
                    ->min(0)
                    ->max(1)
                    ->default(0.65),
                Json::make('Weights', 'weights')->object()->nullable(),
            ]),
        ];
    }

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
            MatchingControlSettingIndexPage::class,
            \MoonShine\Laravel\Pages\Crud\FormPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function rules(Model $item): array
    {
        return [
            'interval_minutes' => ['required', 'integer', 'min:1'],
            'default_scope' => ['required', 'string', 'in:all,profiles,entities'],
            'provider' => ['required', 'string', 'max:64'],
            'model' => ['required', 'string', 'max:128'],
            'score_threshold' => ['required', 'numeric', 'between:0,1'],
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
