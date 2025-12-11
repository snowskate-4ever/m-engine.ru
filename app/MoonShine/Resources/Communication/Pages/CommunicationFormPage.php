<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Communication\Pages;

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use App\MoonShine\Resources\Communication\CommunicationResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Text;
use App\Models\User;
use App\Models\Resource;
use App\Models\Type;
use App\MoonShine\Resources\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\UI\Fields\Field;
use Throwable;


/**
 * @extends FormPage<CommunicationResource>
 */
class CommunicationFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                BelongsTo::make(
                        __('moonshine.communications.user'),
                        'user',
                        formatted: static fn (User $model) => $model->name,
                    )
                        ->creatable(),
                BelongsTo::make(
                        __('moonshine.communications.resource'),
                        'resource',
                        formatted: static fn (Resource $model) => $model->name,
                    )
                        ->creatable(),
                BelongsTo::make(
                        __('moonshine.resources.resource_type'),
                        'type',
                        formatted: static fn (Type $model) => __('moonshine.types.values.'.$model->name),
                    )
                        ->creatable()
                        ->valuesQuery(fn(Builder $query, Field $field) => $query->where('resource_type', 'communication')),
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
