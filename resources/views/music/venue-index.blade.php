@php
    $titles = [
    'studio' => __('ui.music.studios_index'),
    'rehearsal' => __('ui.music.rehearsals_index'),
    'school' => __('ui.music.schools_index'),
];
@endphp
<x-layouts.second_level_layout :title="$titles[$kind] ?? ''" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.venue-index-page :kind="$kind" />
    </div>
</x-layouts.second_level_layout>
