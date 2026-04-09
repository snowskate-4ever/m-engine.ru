@php
    $titles = [
    'studio' => __('ui.music.studios_index'),
    'rehearsal' => __('ui.music.rehearsals_index'),
    'school' => __('ui.music.schools_index'),
    'record_label' => __('ui.music.labels_index'),
    'producer_center' => __('ui.music.producer-centers_index'),
    'shop' => __('ui.music.shops_index'),
];
@endphp
<x-layouts.second_level_layout :title="$titles[$kind] ?? ''" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.venue-index-page :kind="$kind" />
    </div>
</x-layouts.second_level_layout>
