<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Room\Pages;

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Room\RoomResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Checkbox;
use App\Models\Resource;
use Throwable;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Field;
use Illuminate\Contracts\Database\Eloquent\Builder;


/**
 * @extends FormPage<RoomResource>
 */
class RoomFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                Text::make(__('moonshine.rooms.name'), 'name'),
                Number::make(__('moonshine.rooms.square'), 'square'),
                Checkbox::make(__('moonshine.rooms.rent'), 'rent'),
                BelongsTo::make(
                        __('moonshine.rooms.resource'),
                        'resource',
                        formatted: static fn (Resource $model) => $model->name,
                )
                        ->creatable()
                        ->valuesQuery(fn(Builder $query, Field $field) => $query
                            ->select('resources.*', 'types.resource_type')
                            ->leftjoin('types', 'types.id', '=', 'resources.type_id')
                            ->whereIn('types.resource_type', ['place'])),
            ]),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    /**
     * @param  FormBuilder  $component
     *
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
