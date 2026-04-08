<x-layouts.second_level_layout :title="$recordId ? __('ui.music.performer_edit') : __('ui.music.performer_create')" :buttons="[]">
    <div class="min-w-0 flex-1">
        <livewire:music.performer-edit-page :record-id="$recordId" />
    </div>
</x-layouts.second_level_layout>
