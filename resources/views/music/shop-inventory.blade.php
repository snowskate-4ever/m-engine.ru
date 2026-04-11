<x-layouts.second_level_layout :title="__('ui.music.shop_inventory_page_title', ['name' => $shop->name])" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.shop-inventory-page :shop-id="$shop->id" />
    </div>
</x-layouts.second_level_layout>
