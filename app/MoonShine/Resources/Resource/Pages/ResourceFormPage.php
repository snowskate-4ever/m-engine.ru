<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource\Pages;

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Resource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Field;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Number;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use App\Models\Resource as MResource;
use App\Models\Type;
use App\MoonShine\Resources\Resource\ResourceResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Throwable;


/**
 * @extends FormPage<Resource>
 */
class ResourceFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make(__('moonshine.resources.name'), 'name'),
                Textarea::make(__('moonshine.resources.description'), 'description'),
                Checkbox::make(__('moonshine.resources.active'), 'active'),
                BelongsTo::make(
                            __('moonshine.resources.resource_type'),
                            'type',
                            formatted: static fn (Type $model) => __('moonshine.types.values.'.$model->name),
                    )
                        ->creatable()
                        ->valuesQuery(fn(Builder $query) => $query->where('resource_type', 'resources')),
                Date::make(__('moonshine.resources.start_at'), 'start_at'),
                Date::make(__('moonshine.resources.end_at'), 'end_at')
                    ->nullable(),
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
