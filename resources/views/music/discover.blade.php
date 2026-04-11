<x-layouts.second_level_layout :title="__('ui.music.discover_title')" :title-in-top-bar="true" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.music-directory-page :show-heading="false" />
    </div>
</x-layouts.second_level_layout>
