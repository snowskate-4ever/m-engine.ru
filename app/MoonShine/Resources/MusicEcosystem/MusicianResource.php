<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\MusicEcosystem;

use App\Models\Musician;
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
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Musician>
 */
final class MusicianResource extends ModelResource
{
    protected string $model = Musician::class;

    public function getTitle(): string
    {
        return 'Музыканты (публичный профиль)';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with('user')->orderBy('name');
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            Text::make('Имя', 'name')->sortable(),
            Text::make('Slug', 'slug'),
            Switcher::make('Публикация', 'public_page_enabled'),
            ...MusicModerationForm::indexModerationColumn(),
            BelongsTo::make(
                'Аккаунт',
                'user',
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
                Text::make('Имя', 'name')->required(),
                BelongsTo::make(
                    'Пользователь',
                    'user',
                    formatted: static fn (?User $u) => $u ? ($u->email ?? $u->name) : '—',
                    resource: UserResource::class,
                )
                    ->nullable()
                    ->hint('Связь 1:1 с аккаунтом'),
                Text::make('Slug', 'slug')->nullable(),
                Switcher::make('Публичная страница', 'public_page_enabled')->default(false),
                Textarea::make('Описание', 'description')->nullable(),
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('musicians', 'slug')->ignore($item->getKey()),
            ],
            'public_page_enabled' => ['boolean'],
            'description' => ['nullable', 'string'],
            'layout_draft' => ['nullable', 'array'],
            'layout_published' => ['nullable', 'array'],
            'moderation_hidden_at' => ['nullable', 'date'],
            'moderation_review_requested_at' => ['nullable', 'date'],
            'moderation_reason' => ['nullable', 'string'],
        ];
    }
}
