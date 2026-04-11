<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shop;

use App\Enums\LegalEntityType;
use App\Models\Shop;
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
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Shop>
 */
final class ShopResource extends ModelResource
{
    protected string $model = Shop::class;

    public function getTitle(): string
    {
        return 'Магазины (музыка)';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with('owner')->orderBy('name');
    }

    /**
     * @return array<string, string>
     */
    private static function legalTypeOptions(): array
    {
        return [
            LegalEntityType::Individual->value => 'Физлицо / ИП',
            LegalEntityType::LegalPerson->value => 'Юрлицо',
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
                Text::make('URL slug', 'slug')
                    ->nullable()
                    ->hint('латиница, цифры, дефис'),
                Switcher::make('Публичная страница', 'public_page_enabled')->default(false),
                Json::make('Вёрстка (черновик)', 'layout_draft')
                    ->nullable()
                    ->hint(MusicAdminHints::LAYOUT_DRAFT),
                Json::make('Вёрстка (опубликовано)', 'layout_published')
                    ->nullable()
                    ->hint(MusicAdminHints::LAYOUT_PUBLISHED),
                Select::make('Тип лица', 'legal_entity_type')
                    ->options(self::legalTypeOptions())
                    ->nullable(),
                Text::make('Название компании', 'company_name')->nullable(),
                Text::make('ИНН', 'inn')->nullable(),
                Text::make('ОГРН/ОГРНИП', 'ogrn')->nullable(),
                ...MusicModerationForm::shopFields(),
            ]),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): array
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name'),
                Textarea::make('Описание', 'description')->nullable(),
                BelongsTo::make(
                    'Владелец',
                    'owner',
                    formatted: static fn (?User $u) => $u ? ($u->email ?? $u->name) : '—',
                    resource: UserResource::class,
                )->nullable(),
                Text::make('Slug', 'slug'),
                Switcher::make('Публичная страница', 'public_page_enabled'),
                Json::make('Вёрстка (черновик)', 'layout_draft')
                    ->nullable()
                    ->hint(MusicAdminHints::LAYOUT_DRAFT),
                Json::make('Вёрстка (опубликовано)', 'layout_published')
                    ->nullable()
                    ->hint(MusicAdminHints::LAYOUT_PUBLISHED),
                Select::make('Тип лица', 'legal_entity_type')
                    ->options(self::legalTypeOptions())
                    ->nullable(),
                Text::make('Компания', 'company_name')->nullable(),
                Text::make('ИНН', 'inn')->nullable(),
                Text::make('ОГРН', 'ogrn')->nullable(),
                ...MusicModerationForm::shopFields(),
                HasMany::make('Позиции (SKU)', 'items', resource: ShopItemResource::class),
                HasMany::make('Заказы', 'orders', resource: ShopOrderResource::class),
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

    public function search(): array
    {
        return ['id', 'name', 'slug', 'inn'];
    }

    public function rules(Model $item): array
    {
        $legalValues = array_map(static fn (LegalEntityType $c) => $c->value, LegalEntityType::cases());

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('shops', 'slug')->ignore($item->getKey()),
            ],
            'public_page_enabled' => ['boolean'],
            'layout_draft' => ['nullable', 'array'],
            'layout_published' => ['nullable', 'array'],
            'legal_entity_type' => ['nullable', 'in:'.implode(',', $legalValues)],
            'company_name' => ['nullable', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:32'],
            'ogrn' => ['nullable', 'string', 'max:32'],
            'platform_fee_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'moderation_hidden_at' => ['nullable', 'date'],
            'moderation_review_requested_at' => ['nullable', 'date'],
            'moderation_reason' => ['nullable', 'string'],
        ];
    }
}
