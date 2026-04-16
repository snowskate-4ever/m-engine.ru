<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Enums\AutomationPresetType;
use App\Models\AutomationPresetSetting;
use App\Support\Admin\AutomationPresetOwnerLookup;
use App\Models\ConcertVenue;
use App\Models\Musician;
use App\Models\Peformer;
use App\Models\ProducerCenter;
use App\Models\RecordLabel;
use App\Models\Rehersal;
use App\Models\School;
use App\Models\Shop;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\DTOs\Select\AsyncSettings;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
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
            Select::make('Preset', 'preset_type')
                ->options($this->presetOptions()),
            Select::make('Owner Type', 'owner_type')
                ->options($this->ownerTypeOptions()),
            Text::make('Owner ID', 'owner_id'),
            Switcher::make('Enabled', 'is_enabled'),
            Text::make('Board Name', 'settings->board_name'),
            Text::make('Column Name', 'settings->column_name'),
        ];
    }

    protected function formFields(): array
    {
        return [
            Box::make('Preset', [
                ID::make()->disabled(),
                Select::make('Preset', 'preset_type')
                    ->options($this->presetOptions())
                    ->required()
                    ->hint('Какое событие запускает автоматизацию: создание объявления, новый отклик и т.д.'),
                Select::make('Owner Type', 'owner_type')
                    ->options($this->ownerTypeOptions())
                    ->nullable()
                    ->hint('Опционально: ограничить по типу владельца (агент/перформер/площадка и т.д.).'),
                Select::make('Owner', 'owner_id')
                    ->nullable()
                    ->hint('Поиск по имени/email или id; данные подгружаются асинхронно после выбора типа владельца.')
                    ->async(fn (): string => route('moonshine.async.automation-preset-owner'))
                    ->asyncSettings(
                        AsyncSettings::make()
                            ->withAllFields()
                            ->selectedValuesKey('owner_id')
                    )
                    ->asyncOnInit(true),
                Switcher::make('Enabled', 'is_enabled')->default(true),
                Text::make('Board Name', 'settings->board_name')
                    ->nullable()
                    ->hint('Имя доски для карточек, по умолчанию "Объявления".'),
                Text::make('Column Name', 'settings->column_name')
                    ->nullable()
                    ->hint('Имя колонки, по умолчанию зависит от пресета.'),
                Json::make('Settings', 'settings')
                    ->object()
                    ->nullable()
                    ->hint('Дополнительные параметры пресета в JSON.'),
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
            'preset_type' => ['required', 'string', 'in:'.implode(',', array_map(
                static fn (AutomationPresetType $case): string => $case->value,
                AutomationPresetType::cases()
            ))],
            'owner_type' => ['nullable', 'string', 'in:'.implode(',', array_keys($this->ownerTypeOptions()))],
            'owner_id' => [
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail) use ($item): void {
                    if ($value === null || $value === '' || $value === 0) {
                        return;
                    }

                    $type = $this->requestField('owner_type');
                    if (! is_string($type) || $type === '') {
                        $type = $item->getAttribute('owner_type');
                    }
                    if (! is_string($type) || $type === '') {
                        $fail('Укажите тип владельца, если задан owner id.');

                        return;
                    }

                    $table = AutomationPresetOwnerLookup::table($type);
                    if ($table === null) {
                        $fail('Неизвестный тип владельца.');

                        return;
                    }

                    if (! DB::table($table)->where('id', (int) $value)->exists()) {
                        $fail('Указанный owner id не найден для выбранного типа.');
                    }
                },
            ],
            'settings.board_name' => ['nullable', 'string', 'max:120'],
            'settings.column_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function presetOptions(): array
    {
        $options = [];
        foreach (AutomationPresetType::cases() as $case) {
            $options[$case->value] = $case->value;
        }

        return $options;
    }

    /**
     * @return array<string,string>
     */
    private function ownerTypeOptions(): array
    {
        return [
            User::class => 'User (agent/organizer)',
            Peformer::class => 'Performer',
            Musician::class => 'Musician',
            ConcertVenue::class => 'Concert venue',
            Studio::class => 'Studio',
            Rehersal::class => 'Rehearsal base',
            School::class => 'School',
            RecordLabel::class => 'Record label',
            ProducerCenter::class => 'Producer center',
            Shop::class => 'Shop',
        ];
    }

    private function requestField(string $column): mixed
    {
        if (request()->has($column)) {
            return request()->input($column);
        }

        foreach (request()->all() as $value) {
            if (is_array($value) && array_key_exists($column, $value)) {
                return $value[$column];
            }
        }

        return null;
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
