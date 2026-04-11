<div class="mx-auto w-full max-w-3xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">{{ __('ui.music.shop_cart_title') }}</flux:heading>
        <flux:button :href="route('music.shop.orders')" variant="ghost" wire:navigate>{{ __('ui.music.shop_my_orders') }}</flux:button>
    </div>

    <flux:error name="cart" />

    @if ($lines->isEmpty())
        <flux:callout variant="secondary">{{ __('ui.music.shop_cart_empty') }}</flux:callout>
    @else
        @foreach ($grouped as $shopId => $shopLines)
            @php $shop = $shopLines->first()->shopItem->shop; @endphp
            <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $shop->name }}</h2>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($shopLines as $line)
                        <li class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $line->shopItem->displayTitle() }}</div>
                                <div class="text-xs text-zinc-500">{{ __('ui.music.shop_inventory_price') }}: {{ $line->shopItem->price }} × {{ $line->quantity }}</div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    type="number"
                                    value="{{ $line->quantity }}"
                                    wire:blur="updateQty({{ $line->id }}, $event.target.value)"
                                    min="1"
                                    max="{{ $line->shopItem->stock_quantity }}"
                                    class="w-20 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-900"
                                />
                                <flux:button size="sm" variant="ghost" wire:click="remove({{ $line->id }})">{{ __('ui.delete') }}</flux:button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:field>
                <flux:label>{{ __('ui.music.shop_delivery_mode') }}</flux:label>
                <div class="mt-2 flex flex-col gap-2 text-sm">
                    <label class="flex cursor-pointer items-center gap-2 text-zinc-800 dark:text-zinc-200">
                        <input type="radio" wire:model.live="delivery_mode" value="pickup" class="rounded-full border-zinc-300 text-zinc-900 dark:border-zinc-600" />
                        {{ __('ui.music.shop_delivery_pickup') }}
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-zinc-800 dark:text-zinc-200">
                        <input type="radio" wire:model.live="delivery_mode" value="shipping" class="rounded-full border-zinc-300 text-zinc-900 dark:border-zinc-600" />
                        {{ __('ui.music.shop_delivery_shipping') }}
                    </label>
                </div>
            </flux:field>
            @if ($delivery_mode === 'shipping')
                <flux:field>
                    <flux:label>{{ __('ui.music.shop_delivery_address') }}</flux:label>
                    <select
                        wire:model="shipping_address_id"
                        class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                    >
                        <option value="">{{ __('ui.music.shop_delivery_address_placeholder') }}</option>
                        @foreach ($shippingAddresses as $addr)
                            <option value="{{ $addr->id }}">{{ $addr->short_address }}@if(filled($addr->name)) — {{ $addr->name }}@endif</option>
                        @endforeach
                    </select>
                </flux:field>
                @if ($shippingAddresses->isEmpty())
                    <flux:callout variant="secondary">{{ __('ui.music.shop_cart_no_shipping_addresses') }}</flux:callout>
                @endif
            @endif
            <flux:field>
                <flux:label>{{ __('ui.music.shop_checkout_note') }}</flux:label>
                <flux:textarea wire:model="buyer_note" rows="2" />
            </flux:field>
            <flux:description>{{ __('ui.music.shop_checkout_split_hint') }}</flux:description>
            <flux:button type="button" variant="primary" wire:click="checkout">{{ __('ui.music.shop_checkout_submit') }}</flux:button>
        </div>
    @endif
</div>
