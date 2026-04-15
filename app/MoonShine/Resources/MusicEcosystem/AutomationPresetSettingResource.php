<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Models\AutomationPresetSetting;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<AutomationPresetSetting>
 */
#[Icon('adjustments-horizontal')]
#[Group('Music', 'music')]
final class AutomationPresetSettingResource extends ModelResource
{
    protected string $model = AutomationPresetSetting::class;

    public function getTitle(): string
    {
        return 'Automation Presets';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->latest('id');
    }

    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Preset', 'preset_type'),
            Text::make('Owner Type', 'owner_type'),
            Text::make('Owner ID', 'owner_id'),
            Switcher::make('Enabled', 'is_enabled'),
        ];
    }

    protected function formFields(): array
    {
        return [
            Box::make('Preset', [
                ID::make()->disabled(),
                Text::make('Preset', 'preset_type')->required(),
                Text::make('Owner Type', 'owner_type')->nullable(),
                Text::make('Owner ID', 'owner_id')->nullable(),
                Switcher::make('Enabled', 'is_enabled')->default(true),
                Json::make('Settings', 'settings')->object()->nullable(),
            ]),
        ];
    }

    protected function detailFields(): array
    {
        return $this->formFields();
    }

    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\Crud\IndexPage::class,
            \MoonShine\Laravel\Pages\Crud\FormPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function rules(Model $item): array
    {
        return [
            'preset_type' => ['required', 'string', 'max:64'],
            'owner_type' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', 'min:1'],
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
