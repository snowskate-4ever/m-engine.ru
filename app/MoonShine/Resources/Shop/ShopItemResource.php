<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shop;

use App\Enums\ShopItemCondition;
use App\Models\Good;
use App\Models\Shop;
use App\Models\ShopItem;
use App\MoonShine\Resources\Good\GoodResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<ShopItem>
 */
final class ShopItemResource extends ModelResource
{
    protected string $model = ShopItem::class;

    public function getTitle(): string
    {
        return 'Позиции магазинов (SKU)';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with(['shop', 'good'])->orderBy('shop_id')->orderBy('code');
    }

    /**
     * @return array<string, string>
     */
    private static function conditionOptions(): array
    {
        return [
            ShopItemCondition::New->value => 'Новый',
            ShopItemCondition::Used->value => 'Б/у',
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            BelongsTo::make(
                'Магазин',
                'shop',
                formatted: static fn (Shop $s) => $s->name,
                resource: ShopResource::class,
            )->searchable(),
            Text::make('Артикул', 'code')->sortable(),
            Select::make('Состояние', 'condition')
                ->options(self::conditionOptions()),
            Number::make('Цена', 'price'),
            Number::make('Остаток', 'stock_quantity'),
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
                BelongsTo::make(
                    'Магазин',
                    'shop',
                    formatted: static fn (Shop $s) => $s->name,
                    resource: ShopResource::class,
                )->required(),
                BelongsTo::make(
                    'Товар каталога',
                    'good',
                    formatted: static fn (?Good $g) => $g ? ($g->name ?? $g->code ?? '#'.$g->getKey()) : '—',
                    resource: GoodResource::class,
                )->nullable(),
                Text::make('Артикул (SKU)', 'code')
                    ->required(),
                Select::make('Состояние', 'condition')
                    ->options(self::conditionOptions())
                    ->required(),
                Text::make('Заголовок (переопределение)', 'title_override')->nullable(),
                Textarea::make('Описание (переопределение)', 'description_override')->nullable(),
                Text::make('Цена', 'price')->required(),
                Number::make('Остаток', 'stock_quantity')->min(0)->required(),
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
        return ['id', 'code', 'title_override'];
    }

    public function rules(Model $item): array
    {
        $shopId = (int) $item->shop_id;
        $itemId = $item->getKey();

        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'good_id' => ['nullable', 'exists:goods,id'],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shop_items', 'code')->where('shop_id', $shopId)->ignore($itemId),
            ],
            'condition' => ['required', Rule::in(array_column(ShopItemCondition::cases(), 'value'))],
            'title_override' => ['nullable', 'string', 'max:255'],
            'description_override' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
