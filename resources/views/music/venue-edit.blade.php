@php
    $titles = [
    'studio' => $recordId ? __('ui.music.studios_edit') : __('ui.music.studios_create'),
    'rehearsal' => $recordId ? __('ui.music.rehearsals_edit') : __('ui.music.rehearsals_create'),
    'school' => $recordId ? __('ui.music.schools_edit') : __('ui.music.schools_create'),
    'record_label' => $recordId ? __('ui.music.labels_edit') : __('ui.music.labels_create'),
    'producer_center' => $recordId ? __('ui.music.producer-centers_edit') : __('ui.music.producer-centers_create'),
    'shop' => $recordId ? __('ui.music.shops_edit') : __('ui.music.shops_create'),
];
@endphp
<x-layouts.second_level_layout :title="$titles[$kind] ?? ''" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.venue-edit-page :kind="$kind" :record-id="$recordId" />
    </div>
</x-layouts.second_level_layout>
