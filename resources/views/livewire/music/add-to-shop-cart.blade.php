<div class="mt-2">
    @if (! $isAuthed)
        <a href="{{ route('login') }}" class="text-xs font-medium text-zinc-600 underline-offset-2 hover:underline dark:text-zinc-400">{{ __('ui.music.shop_cart_login_to_buy') }}</a>
    @elseif ($shopItem && $shopItem->stock_quantity < 1)
        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.music.shop_public_out_of_stock') }}</span>
    @elseif ($shopItem)
        @if (filled($notice))
            <p class="mb-1 text-xs text-emerald-600 dark:text-emerald-400">{{ $notice }}</p>
        @endif
        <form wire:submit="add" class="flex flex-wrap items-end gap-2">
            <label class="flex flex-col gap-0.5">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.music.shop_cart_qty') }}</span>
                <input
                    type="number"
                    wire:model="quantity"
                    min="1"
                    max="{{ $shopItem->stock_quantity }}"
                    class="w-16 rounded-lg border border-zinc-300 bg-white px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-900"
                />
            </label>
            <button
                type="submit"
                class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
            >{{ __('ui.music.shop_cart_add') }}</button>
        </form>
        <flux:error name="quantity" />
    @endif
</div>
