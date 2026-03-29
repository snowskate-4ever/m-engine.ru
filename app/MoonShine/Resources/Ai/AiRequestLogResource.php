<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Ai;

use App\Enums\AiRequestSource;
use App\Enums\AiRequestStatus;
use App\Models\AiRequestLog;
use App\Models\User;
use App\MoonShine\Resources\Ai\Pages\AiRequestLogIndexPage;
use Illuminate\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<AiRequestLog>
 */
final class AiRequestLogResource extends ModelResource
{
    protected string $model = AiRequestLog::class;

    public function getTitle(): string
    {
        return __('moonshine.ai.request_logs_tab');
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->orderByDesc($builder->getModel()->getQualifiedKeyName());
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
                formatted: static fn (User $model) => $model->email ?? (string) $model->getKey(),
            )->searchable(),
            Text::make(__('moonshine.ai.conversation_id'), 'conversation_id')->nullable(),
            Enum::make(__('moonshine.ai.source'), 'source')->attach(AiRequestSource::class),
            Enum::make(__('moonshine.ai.status'), 'status')->attach(AiRequestStatus::class),
            Number::make(__('moonshine.ai.duration_ms'), 'duration_ms'),
            Number::make(__('moonshine.ai.tokens_prompt'), 'tokens_prompt'),
            Number::make(__('moonshine.ai.tokens_completion'), 'tokens_completion'),
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
            Box::make(__('moonshine.ai.request_log_box'), [
                ID::make(),
                Date::make(__('moonshine.ai.created_at'), 'created_at'),
                BelongsTo::make(
                    __('moonshine.ai.user'),
                    'user',
                    formatted: static fn (User $model) => $model->email ?? (string) $model->getKey(),
                ),
                Text::make(__('moonshine.ai.conversation_id'), 'conversation_id')->nullable(),
                Enum::make(__('moonshine.ai.source'), 'source')->attach(AiRequestSource::class),
                Text::make(__('moonshine.ai.ai_server_model_id'), 'ai_server_model_id')->nullable(),
                Text::make(__('moonshine.ai.user_ai_connection_id'), 'user_ai_connection_id')->nullable(),
                Enum::make(__('moonshine.ai.status'), 'status')->attach(AiRequestStatus::class),
                Number::make(__('moonshine.ai.duration_ms'), 'duration_ms'),
                Number::make(__('moonshine.ai.http_status'), 'http_status')->nullable(),
                Text::make(__('moonshine.ai.provider_error_code'), 'provider_error_code')->nullable(),
                Textarea::make(__('moonshine.ai.error_message'), 'error_message')->nullable(),
                Number::make(__('moonshine.ai.tokens_prompt'), 'tokens_prompt'),
                Number::make(__('moonshine.ai.tokens_completion'), 'tokens_completion'),
                Text::make(__('moonshine.ai.estimated_internal_cost'), 'estimated_internal_cost')->nullable(),
                Text::make(__('moonshine.ai.provider_request_id'), 'provider_request_id')->nullable(),
                Textarea::make(__('moonshine.ai.prompt_excerpt'), 'prompt_excerpt')->nullable()->unescape(),
                Textarea::make(__('moonshine.ai.response_excerpt'), 'response_excerpt')->nullable()->unescape(),
            ]),
        ];
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            AiRequestLogIndexPage::class,
            \MoonShine\Laravel\Pages\FormPage::class,
            \MoonShine\Laravel\Pages\DetailPage::class,
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function search(): array
    {
        return ['id', 'provider_request_id', 'provider_error_code'];
    }

    public function filters(): array
    {
        return [
            Select::make(__('moonshine.ai.status'), 'status')
                ->options([
                    AiRequestStatus::Success->value => AiRequestStatus::Success->value,
                    AiRequestStatus::Error->value => AiRequestStatus::Error->value,
                ])
                ->nullable(),
            Select::make(__('moonshine.ai.source'), 'source')
                ->options([
                    AiRequestSource::Server->value => AiRequestSource::Server->value,
                    AiRequestSource::Byok->value => AiRequestSource::Byok->value,
                ])
                ->nullable(),
        ];
    }

    public function permissions(): array
    {
        return [
            'view' => auth()->user()->hasRole('admin') || auth()->user()->hasRole('editor'),
            'create' => false,
            'update' => false,
            'delete' => false,
            'massDelete' => false,
        ];
    }
}
