<x-layouts.second_level_layout
    :title="__('ui.music.musician_page_title')"
    :buttons="[]"
    :content-right-inset="false"
    :top-bar-button="[
        'window_event' => 'open-user-profiles-modal',
        'label' => '+',
        'title' => __('ui.music.music_profile_roles_menu'),
    ]"
>
    <div class="min-w-0 flex-1">
        <livewire:music.musician-profile-page />
    </div>
</x-layouts.second_level_layout>
