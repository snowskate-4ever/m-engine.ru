@php
    $titles = [
        'studio' => __('ui.music.studios_index'),
        'rehearsal' => __('ui.music.rehearsals_index'),
        'concert_venue' => __('ui.music.concert-venues_index'),
        'school' => __('ui.music.schools_index'),
        'record_label' => __('ui.music.labels_index'),
        'producer_center' => __('ui.music.producer-centers_index'),
        'shop' => __('ui.music.shops_index'),
    ];
    $routePrefixes = [
        'studio' => 'studios',
        'rehearsal' => 'rehearsals',
        'concert_venue' => 'concert-venues',
        'school' => 'schools',
        'record_label' => 'labels',
        'producer_center' => 'producer-centers',
        'shop' => 'shops',
    ];
    $routePrefix = $routePrefixes[$kind] ?? null;
@endphp
<x-layouts.second_level_layout
    :title="$titles[$kind] ?? ''"
    :buttons="[]"
    :top-bar-button="$routePrefix ? [
        'href' => route('music.'.$routePrefix.'.create'),
        'label' => '+',
        'title' => __('ui.music.'.$routePrefix.'_create'),
    ] : null"
>
    <div class="min-w-0 flex-1">
        <livewire:music.venue-index-page :kind="$kind" />
    </div>
</x-layouts.second_level_layout>
