<div class="mx-auto w-full max-w-3xl space-y-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">{{ __('ui.music.shop_my_orders') }}</flux:heading>
        <flux:button :href="route('music.shop.cart')" variant="ghost" wire:navigate>{{ __('ui.music.shop_cart_title') }}</flux:button>
    </div>

    @if (session('orders_placed'))
        <flux:callout variant="success">{{ __('ui.music.shop_orders_placed_ok') }}</flux:callout>
    @endif

    @forelse ($orders as $order)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $order->shop->name }}</div>
                    <div class="text-xs text-zinc-500">#{{ $order->id }} · {{ $order->created_at->format('Y-m-d H:i') }}</div>
                </div>
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    {{ $statusLabels[$order->status->value] ?? $order->status->value }}
                </span>
            </div>
            @if (filled($order->buyer_note))
                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $order->buyer_note }}</p>
            @endif
            <ul class="mt-3 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                @foreach ($order->items as $oi)
                    <li>{{ $oi->title_snapshot ?? $oi->shopItem?->code }} × {{ $oi->quantity }} @ {{ $oi->unit_price }}</li>
                @endforeach
            </ul>
        </div>
    @empty
        <flux:callout variant="secondary">{{ __('ui.music.shop_orders_empty') }}</flux:callout>
    @endforelse
</div>
