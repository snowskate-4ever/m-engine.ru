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
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use App\Models\Resource;
use App\Models\Room;
use App\Models\User;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\Room\RoomResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Field;
use Illuminate\Validation\Rule;
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
                // ID не нужен на форме, он отображается автоматически при редактировании
                Text::make(__('moonshine.events.name'), 'name'),
                Textarea::make(__('moonshine.events.description'), 'description'),
                Checkbox::make(__('moonshine.events.active'), 'active'),
                BelongsTo::make(
                        __('moonshine.events.booking_resource'),
                        'bookingResource',
                        formatted: static fn (?Resource $model) => $model?->name ?? '',
                        resource: ResourceResource::class,
                    )
                        ->nullable()
                        ->creatable()
                        ->valuesQuery(fn(Builder $query, Field $field) => $query
                            ->select('resources.*', 'types.resource_type')
                            ->leftjoin('types', 'types.id', '=', 'resources.type_id')
                            ->where('types.resource_type', 'resources')),
                BelongsTo::make(
                        __('moonshine.events.booked_resource'),
                        'bookedResource',
                        formatted: static fn (?Resource $model) => $model?->name ?? '',
                        resource: ResourceResource::class,
                    )
                        ->nullable()
                        ->creatable()
                        ->valuesQuery(fn(Builder $query, Field $field) => $query
                            ->select('resources.*', 'types.resource_type')
                            ->leftjoin('types', 'types.id', '=', 'resources.type_id')
                            ->where('types.resource_type', 'resources')),
                BelongsTo::make(
                        __('moonshine.events.room'),
                        'room',
                        formatted: static fn (?Room $model) => $model ? ($model->name . ($model->relationLoaded('resource') && $model->resource ? ' (' . $model->resource->name . ')' : '')) : '',
                        resource: RoomResource::class,
                    )
                        ->nullable()
                        ->creatable()
                        ->valuesQuery(fn(Builder $query) => $query->where('rent', true)), // Только комнаты доступные для аренды
                BelongsTo::make(
                        __('moonshine.events.user'),
                        'user',
                        formatted: static fn (User $model) => $model->name . ' (' . $model->email . ')',
                        resource: UserResource::class,
                    )
                        ->nullable()
                        ->valuesQuery(fn(Builder $query) => $query),
                Select::make(__('moonshine.events.status'), 'status')
                    ->options([
                        'pending' => __('moonshine.events.statuses.pending'),
                        'confirmed' => __('moonshine.events.statuses.confirmed'),
                        'cancelled' => __('moonshine.events.statuses.cancelled'),
                        'completed' => __('moonshine.events.statuses.completed'),
                    ])
                    ->default('pending'),
                Date::make(__('moonshine.events.start_at'), 'start_at')
                    ->withTime(),
                Date::make(__('moonshine.events.end_at'), 'end_at')
                    ->withTime(),
                Number::make(__('moonshine.events.price'), 'price')
                    ->nullable()
                    ->step(0.01)
                    ->min(0),
                Textarea::make(__('moonshine.events.notes'), 'notes')
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled,completed'],
            'booking_resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'booked_resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];

        // При обновлении делаем name уникальным с игнором текущей записи
        if ($item->getKey()) {
            $rules['name'][] = \Illuminate\Validation\Rule::unique('events', 'name')->ignore($item->getKey());
        } else {
            $rules['name'][] = 'unique:events,name';
        }

        return $rules;
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
