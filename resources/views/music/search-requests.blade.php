<x-layouts.second_level_layout
    :title="__('ui.music.search_requests_page_title')"
    :buttons="[]"
    :content-right-inset="false"
    :top-bar-button="[
        'dispatch' => 'search-requests-open-create',
        'label' => '+',
        'title' => __('ui.music.search_requests_top_bar_create'),
    ]"
>
    <div class="min-w-0 flex-1">
        <livewire:music.search-requests-page />
    </div>
</x-layouts.second_level_layout>
