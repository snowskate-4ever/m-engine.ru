<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\VkTracking;

use App\Models\VkTracking;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<VkTracking>
 */
class VkTrackingResource extends ModelResource
{
    protected string $model = VkTracking::class;

    public function getTitle(): string
    {
        return 'VK отслеживание';
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Название', 'name'),
            Text::make('Screen name', 'screen_name'),
            Number::make('ID группы', 'group_id'),
            Switcher::make('Активен', 'is_active'),
            Text::make('Создан', 'created_at')->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make('Группа ВК', [
                ID::make(),
                Text::make('Название', 'name')
                    ->required()
                    ->max(255),
                Text::make('Screen name', 'screen_name')
                    ->required()
                    ->max(255)
                    ->hint('Например: muzspb'),
                Number::make('ID группы', 'group_id')
                    ->nullable()
                    ->hint('Можно оставить пустым, если неизвестно'),
                Switcher::make('Активен', 'is_active'),
                Textarea::make('Описание', 'description')
                    ->nullable(),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make('Информация', [
                ID::make(),
                Text::make('Название', 'name'),
                Text::make('Screen name', 'screen_name'),
                Number::make('ID группы', 'group_id'),
                Switcher::make('Активен', 'is_active'),
                Textarea::make('Описание', 'description'),
                Text::make('Создан', 'created_at'),
                Text::make('Обновлен', 'updated_at'),
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

    public function rules(Model $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'screen_name' => ['required', 'string', 'max:255'],
            'group_id' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
