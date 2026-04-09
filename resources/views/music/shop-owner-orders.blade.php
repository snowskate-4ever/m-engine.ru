<x-layouts.second_level_layout :title="__('ui.music.shop_owner_orders_heading')" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.shop-owner-orders-page :shop-id="$shop->id" />
    </div>
</x-layouts.second_level_layout>
