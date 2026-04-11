<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shop;

use App\Enums\ShopDeliveryMode;
use App\Enums\ShopOrderStatus;
use App\Enums\ShopPaymentMethod;
use App\Enums\ShopPaymentStatus;
use App\Models\Address;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Models\User;
use App\MoonShine\Resources\Address\AddressResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Support\MusicAdminHints;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<ShopOrder>
 */
final class ShopOrderResource extends ModelResource
{
    protected string $model = ShopOrder::class;

    protected function isCan(Ability $ability): bool
    {
        if ($ability === Ability::CREATE) {
            return false;
        }

        return parent::isCan($ability);
    }

    public function getTitle(): string
    {
        return 'Заказы магазинов';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->with(['shop', 'buyer', 'shippingAddress'])->orderByDesc('created_at');
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        $out = [];
        foreach (ShopOrderStatus::cases() as $case) {
            $out[$case->value] = match ($case) {
                ShopOrderStatus::Pending => 'Ожидает подтверждения',
                ShopOrderStatus::StoreConfirmed => 'Подтверждён магазином',
                ShopOrderStatus::Cancelled => 'Отменён',
            };
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private static function paymentStatusOptions(): array
    {
        $out = [];
        foreach (ShopPaymentStatus::cases() as $case) {
            $out[$case->value] = match ($case) {
                ShopPaymentStatus::Pending => 'Ожидает оплаты',
                ShopPaymentStatus::Paid => 'Оплачен',
                ShopPaymentStatus::Failed => 'Ошибка',
                ShopPaymentStatus::Cancelled => 'Отменён',
                ShopPaymentStatus::Waived => 'Без оплаты',
            };
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private static function paymentMethodOptions(): array
    {
        $out = [];
        foreach (ShopPaymentMethod::cases() as $case) {
            $out[$case->value] = match ($case) {
                ShopPaymentMethod::None => 'Не задано',
                ShopPaymentMethod::Manual => 'Вручную / счёт',
                ShopPaymentMethod::Aggregator => 'Агрегатор (позже)',
            };
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private static function deliveryModeOptions(): array
    {
        $out = [];
        foreach (ShopDeliveryMode::cases() as $case) {
            $out[$case->value] = match ($case) {
                ShopDeliveryMode::Pickup => 'Самовывоз',
                ShopDeliveryMode::Shipping => 'Доставка',
            };
        }

        return $out;
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make(
                'Магазин',
                'shop',
                formatted: static fn (Shop $s) => $s->name,
                resource: ShopResource::class,
            )->searchable(),
            BelongsTo::make(
                'Покупатель',
                'buyer',
                formatted: static fn (User $u) => $u->email ?? (string) $u->name,
                resource: UserResource::class,
            )->searchable(),
            Select::make('Статус', 'status')
                ->options(self::statusOptions()),
            Select::make('Оплата', 'payment_status')
                ->options(self::paymentStatusOptions()),
            Select::make('Доставка', 'delivery_mode')
                ->options(self::deliveryModeOptions()),
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
                )
                    ->required()
                    ->disabled(),
                BelongsTo::make(
                    'Покупатель',
                    'buyer',
                    formatted: static fn (User $u) => $u->email ?? (string) $u->name,
                    resource: UserResource::class,
                )
                    ->required()
                    ->disabled(),
                Select::make('Статус', 'status')
                    ->options(self::statusOptions())
                    ->required()
                    ->hint(MusicAdminHints::SHOP_ORDER_FORM),
                Textarea::make('Примечание покупателя', 'buyer_note')
                    ->nullable()
                    ->disabled(),
                Select::make('Доставка', 'delivery_mode')
                    ->options(self::deliveryModeOptions())
                    ->required(),
                BelongsTo::make(
                    'Адрес доставки',
                    'shippingAddress',
                    formatted: static fn (?Address $a) => $a ? ('#'.$a->id.' '.$a->short_address) : '—',
                    resource: AddressResource::class,
                )->nullable()->searchable(),
                Text::make('Сумма заказа', 'subtotal_amount')->disabled(),
                Text::make('Ставка комиссии', 'platform_fee_rate')->disabled(),
                Text::make('Комиссия платформы', 'platform_fee_amount')->disabled(),
                Text::make('К перечислению магазину', 'shop_payout_amount')->disabled(),
                Select::make('Статус оплаты', 'payment_status')
                    ->options(self::paymentStatusOptions())
                    ->hint('«Оплачен» — зафиксировать получение средств (агрегатор v1 вручную).'),
                Select::make('Способ оплаты', 'payment_method')
                    ->options(self::paymentMethodOptions()),
                Text::make('Внешний id платежа', 'payment_external_reference')->nullable(),
                Date::make('Оплачен в', 'paid_at')->withTime()->nullable(),
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
                BelongsTo::make(
                    'Магазин',
                    'shop',
                    formatted: static fn (Shop $s) => $s->name,
                    resource: ShopResource::class,
                ),
                BelongsTo::make(
                    'Покупатель',
                    'buyer',
                    formatted: static fn (User $u) => $u->email ?? (string) $u->name,
                    resource: UserResource::class,
                ),
                Select::make('Статус', 'status')
                    ->options(self::statusOptions()),
                Textarea::make('Примечание покупателя', 'buyer_note')->nullable(),
                Select::make('Доставка', 'delivery_mode')->options(self::deliveryModeOptions()),
                BelongsTo::make(
                    'Адрес доставки',
                    'shippingAddress',
                    formatted: static fn (?Address $a) => $a ? ('#'.$a->id.' '.$a->short_address) : '—',
                    resource: AddressResource::class,
                )->nullable(),
                Number::make('Сумма', 'subtotal_amount')->disabled(),
                Number::make('Ставка комиссии', 'platform_fee_rate')->disabled(),
                Number::make('Комиссия', 'platform_fee_amount')->disabled(),
                Number::make('Магазину', 'shop_payout_amount')->disabled(),
                Select::make('Статус оплаты', 'payment_status')->options(self::paymentStatusOptions()),
                Select::make('Способ оплаты', 'payment_method')->options(self::paymentMethodOptions()),
                Text::make('Внешний id', 'payment_external_reference')->nullable(),
                Date::make('Оплачен в', 'paid_at')->withTime()->nullable(),
                HasMany::make('Позиции заказа', 'items', resource: ShopOrderItemResource::class),
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
        return ['id'];
    }

    public function rules(Model $item): array
    {
        $values = array_map(static fn (ShopOrderStatus $c) => $c->value, ShopOrderStatus::cases());
        $payStatuses = array_map(static fn (ShopPaymentStatus $c) => $c->value, ShopPaymentStatus::cases());
        $payMethods = array_map(static fn (ShopPaymentMethod $c) => $c->value, ShopPaymentMethod::cases());
        $deliveryModes = array_map(static fn (ShopDeliveryMode $c) => $c->value, ShopDeliveryMode::cases());

        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'buyer_user_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:'.implode(',', $values)],
            'buyer_note' => ['nullable', 'string'],
            'delivery_mode' => ['required', 'in:'.implode(',', $deliveryModes)],
            'shipping_address_id' => ['nullable', 'exists:addresses,id'],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'platform_fee_amount' => ['required', 'numeric', 'min:0'],
            'shop_payout_amount' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:'.implode(',', $payStatuses)],
            'payment_method' => ['required', 'in:'.implode(',', $payMethods)],
            'payment_external_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
