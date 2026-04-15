<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Models\MatchingRunLog;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<MatchingRunLog>
 */
#[Icon('bolt')]
#[Group('Music', 'music')]
final class MatchingRunLogResource extends ModelResource
{
    protected string $model = MatchingRunLog::class;

    public function getTitle(): string
    {
        return 'Matching Runs';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->latest('id');
    }

    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Scope', 'scope'),
            Switcher::make('Automatic', 'is_automatic'),
            Number::make('Processed', 'processed_count'),
            Number::make('Matched', 'matched_count'),
            Number::make('Failed', 'failed_count'),
            Date::make('Started At', 'started_at'),
            Date::make('Finished At', 'finished_at'),
        ];
    }

    protected function detailFields(): array
    {
        return [
            Box::make('Matching Run', [
                ID::make(),
                Text::make('Scope', 'scope'),
                Switcher::make('Automatic', 'is_automatic'),
                Number::make('Processed', 'processed_count'),
                Number::make('Matched', 'matched_count'),
                Number::make('Failed', 'failed_count'),
                Date::make('Started At', 'started_at'),
                Date::make('Finished At', 'finished_at'),
                Json::make('Meta', 'meta')->object()->nullable(),
            ]),
        ];
    }

    protected function formFields(): array
    {
        return [
            Box::make('Run', [
                ID::make()->disabled(),
                Text::make('Scope', 'scope')->required(),
                Switcher::make('Automatic', 'is_automatic'),
                Number::make('Processed', 'processed_count')->default(0),
                Number::make('Matched', 'matched_count')->default(0),
                Number::make('Failed', 'failed_count')->default(0),
                Json::make('Meta', 'meta')->object()->nullable(),
            ]),
        ];
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
            'scope' => ['required', 'string', 'max:64'],
            'processed_count' => ['nullable', 'integer', 'min:0'],
            'matched_count' => ['nullable', 'integer', 'min:0'],
            'failed_count' => ['nullable', 'integer', 'min:0'],
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
