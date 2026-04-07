<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Models\AgentToolInvocation;
use App\Models\User;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<AgentToolInvocation>
 */
final class AgentToolInvocationResource extends ModelResource
{
    protected string $model = AgentToolInvocation::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.agent_tools_tab');
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc('created_at')->orderByDesc($builder->getModel()->getQualifiedKeyName());
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Date::make(__('moonshine.ai.created_at'), 'created_at')->format('d.m.Y H:i'),
            BelongsTo::make(
                __('moonshine.ai.user'),
                'user',
                formatted: static fn (User $u) => $u->email ?? (string) $u->getKey(),
                resource: UserResource::class,
            )->searchable(),
            Text::make(__('moonshine.ai.tool_name'), 'tool_name'),
            Text::make(__('moonshine.ai.arguments_hash'), 'arguments_hash'),
            Switcher::make('OK', 'ok')->disabled(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return $this->detailFields();
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make(__('moonshine.ai.agent_tool_invocation_box'), [
                ID::make(),
                Date::make(__('moonshine.ai.created_at'), 'created_at'),
                BelongsTo::make(
                    __('moonshine.ai.user'),
                    'user',
                    formatted: static fn (User $u) => $u->email ?? (string) $u->getKey(),
                    resource: UserResource::class,
                ),
                Text::make(__('moonshine.ai.conversation_id'), 'conversation_id')->nullable(),
                Text::make(__('moonshine.ai.tool_name'), 'tool_name'),
                Text::make(__('moonshine.ai.arguments_hash'), 'arguments_hash'),
                Switcher::make('OK', 'ok')->disabled(),
                Textarea::make(__('moonshine.ai.error_message'), 'error_message')->nullable(),
            ]),
        ];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            \MoonShine\Laravel\Pages\Crud\IndexPage::class,
            \MoonShine\Laravel\Pages\Crud\FormPage::class,
            \MoonShine\Laravel\Pages\Crud\DetailPage::class,
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function search(): array
    {
        return ['id', 'tool_name', 'arguments_hash'];
    }

    public function permissions(): array
    {
        $u = auth()->user();

        return [
            'view' => $u && ($u->hasRole('admin') || $u->hasRole('editor')),
            'create' => false,
            'update' => false,
            'delete' => false,
            'massDelete' => false,
        ];
    }
}
