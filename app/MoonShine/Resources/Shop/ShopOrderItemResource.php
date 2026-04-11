<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shop;

use App\Models\ShopItem;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<ShopOrderItem>
 */
final class ShopOrderItemResource extends ModelResource
{
    protected string $model = ShopOrderItem::class;

    protected function isCan(Ability $ability): bool
    {
        if (\in_array($ability, [
            Ability::CREATE,
            Ability::UPDATE,
            Ability::DELETE,
            Ability::MASS_DELETE,
            Ability::RESTORE,
            Ability::FORCE_DELETE,
        ], true)) {
            return false;
        }

        return parent::isCan($ability);
    }

    public function getTitle(): string
    {
        return 'Строки заказов (магазин)';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with(['order.shop', 'shopItem'])->orderByDesc($builder->getModel()->getQualifiedKeyName());
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make(),
            BelongsTo::make(
                'Заказ',
                'order',
                formatted: static fn (ShopOrder $o) => '#'.$o->getKey().' · '.$o->shop?->name,
                resource: ShopOrderResource::class,
            ),
            BelongsTo::make(
                'SKU',
                'shopItem',
                formatted: static fn (ShopItem $i) => $i->code,
                resource: ShopItemResource::class,
            ),
            Number::make('Количество', 'quantity'),
            Text::make('Цена', 'unit_price'),
            Text::make('Название (снимок)', 'title_snapshot'),
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
                    'Заказ',
                    'order',
                    formatted: static fn (ShopOrder $o) => '#'.$o->getKey(),
                    resource: ShopOrderResource::class,
                )->required(),
                BelongsTo::make(
                    'Позиция магазина',
                    'shopItem',
                    formatted: static fn (ShopItem $i) => $i->code,
                    resource: ShopItemResource::class,
                )->required(),
                Number::make('Количество', 'quantity')->min(1)->required(),
                Text::make('Цена за ед.', 'unit_price')->required(),
                Text::make('Название (снимок)', 'title_snapshot')->nullable(),
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
        return ['id', 'title_snapshot'];
    }

    public function rules(Model $item): array
    {
        return [
            'shop_order_id' => ['required', 'exists:shop_orders,id'],
            'shop_item_id' => ['required', 'exists:shop_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'title_snapshot' => ['nullable', 'string', 'max:255'],
        ];
    }
}
