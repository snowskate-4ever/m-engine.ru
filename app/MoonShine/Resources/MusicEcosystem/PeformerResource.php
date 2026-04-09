<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Enums\PerformerKind;
use App\Models\Peformer;
use App\Models\User;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Support\MusicAdminHints;
use App\MoonShine\Support\MusicModerationForm;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Peformer>
 */
final class PeformerResource extends ModelResource
{
    protected string $model = Peformer::class;

    public function getTitle(): string
    {
        return 'Исполнители / коллективы';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with('owner')->orderBy('name');
    }

    /**
     * @return array<string, string>
     */
    private static function kindOptions(): array
    {
        return [
            PerformerKind::Band->value => 'Коллектив',
            PerformerKind::SoloProject->value => 'Сольный проект',
            PerformerKind::Other->value => 'Другое',
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Slug', 'slug'),
            Select::make('Тип', 'performer_kind')->options(self::kindOptions()),
            Switcher::make('Публикация', 'public_page_enabled'),
            ...MusicModerationForm::indexModerationColumn(),
            BelongsTo::make(
                'Владелец',
                'owner',
                formatted: static fn (?User $u) => $u ? ($u->email ?? $u->name) : '—',
                resource: UserResource::class,
            )->nullable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): array
    {
        return [
            Box::make([
                ID::make()->disabled(),
                Text::make('Название', 'name')->required(),
                Textarea::make('Описание', 'description')->nullable(),
                BelongsTo::make(
                    'Владелец',
                    'owner',
                    formatted: static fn (?User $u) => $u ? ($u->email ?? $u->name) : '—',
                    resource: UserResource::class,
                )->nullable(),
                Select::make('Тип', 'performer_kind')
                    ->options(self::kindOptions())
                    ->nullable(),
                Text::make('Slug', 'slug')->nullable(),
                Switcher::make('Публичная страница', 'public_page_enabled')->default(false),
                Json::make('Вёрстка (черновик)', 'layout_draft')
                    ->nullable()
                    ->hint(MusicAdminHints::LAYOUT_DRAFT),
                Json::make('Вёрстка (опубликовано)', 'layout_published')
                    ->nullable()
                    ->hint(MusicAdminHints::LAYOUT_PUBLISHED),
                ...MusicModerationForm::profileFields(),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return $this->formFields();
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

    public function search(): array
    {
        return ['id', 'name', 'slug'];
    }

    public function rules(Model $item): array
    {
        $kindValues = array_map(static fn (PerformerKind $c) => $c->value, PerformerKind::cases());

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'performer_kind' => ['nullable', 'in:'.implode(',', $kindValues)],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('peformers', 'slug')->ignore($item->getKey()),
            ],
            'public_page_enabled' => ['boolean'],
            'layout_draft' => ['nullable', 'array'],
            'layout_published' => ['nullable', 'array'],
            'moderation_hidden_at' => ['nullable', 'date'],
            'moderation_review_requested_at' => ['nullable', 'date'],
            'moderation_reason' => ['nullable', 'string'],
        ];
    }
}
