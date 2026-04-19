<x-layouts.second_level_layout
    :title="__('ui.music.performers_index')"
    :buttons="[]"
    :top-bar-button="[
        'dispatch' => 'music-performers-open-create',
        'label' => '+',
        'title' => __('ui.music.performer_create'),
    ]"
>
    <div class="min-w-0 flex-1">
        <livewire:music.performers-index-page />
    </div>
</x-layouts.second_level_layout>
