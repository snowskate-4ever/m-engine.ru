@php
    $titles = [
    'studio' => $recordId ? __('ui.music.studios_edit') : __('ui.music.studios_create'),
    'rehearsal' => $recordId ? __('ui.music.rehearsals_edit') : __('ui.music.rehearsals_create'),
    'school' => $recordId ? __('ui.music.schools_edit') : __('ui.music.schools_create'),
];
@endphp
<x-layouts.second_level_layout :title="$titles[$kind] ?? ''" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.venue-edit-page :kind="$kind" :record-id="$recordId" />
    </div>
</x-layouts.second_level_layout>
