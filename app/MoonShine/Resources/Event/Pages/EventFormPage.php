<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Event\Pages;

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Event\EventResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use App\Models\Resource;
use App\Models\Room;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Field;
use Throwable;


/**
 * @extends FormPage<EventResource>
 */
class EventFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make(__('moonshine.events.name'), 'name'),
                Textarea::make(__('moonshine.events.description'), 'description'),
                Checkbox::make(__('moonshine.events.active'), 'active'),
                BelongsTo::make(
                        __('moonshine.events.room'),
                        'room',
                        formatted: static fn (Room $model) => $model->name.' - '.$model->resource_name,
                )
                        ->valuesQuery(fn(Builder $query, Field $field) => $query
                            ->select('rooms.*', 
                                'resources.name as resource_name',
                                'resources.type_id',
                                'types.resource_type'
                                )
                            ->leftjoin('resources', 'resources.id', '=', 'rooms.resource_id')
                            ->leftjoin('types', 'types.id', '=', 'resources.type_id')
                            ->whereIn('types.resource_type', ['place']))
                        ->creatable(),
                BelongsTo::make(
                        __('moonshine.events.resource'),
                        'resource',
                        formatted: static fn (Resource $model) => $model->name,
                    )
                        ->creatable()
                        ->valuesQuery(fn(Builder $query, Field $field) => $query
                            ->select('resources.*', 'types.resource_type')
                            ->leftjoin('types', 'types.id', '=', 'resources.type_id')
                            ->whereIn('types.resource_type', ['peformer'])),
                Date::make(__('moonshine.events.start_at'), 'start_at')
                    ->withTime(),
                Date::make(__('moonshine.events.end_at'), 'end_at')
                    ->withTime(),
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
