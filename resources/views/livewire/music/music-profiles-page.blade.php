<div class="mx-auto w-full max-w-3xl space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800/60">
        <label for="music-profile-tab-select" class="sr-only">{{ __('ui.music.profiles_tabs_label') }}</label>
        <select
            id="music-profile-tab-select"
            wire:model.live="tab"
            aria-label="{{ __('ui.music.profiles_tabs_label') }}"
            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
        >
            <option value="musician">{{ __('ui.public_profile.type_musician') }}</option>
            <option value="teacher">{{ __('ui.public_profile.type_teacher') }}</option>
            <option value="organizer">{{ __('ui.music.profile_tab_organizer') }}</option>
            <option value="manager">{{ __('ui.music.profile_tab_manager') }}</option>
            <option value="session_musician">{{ __('ui.music.profile_tab_session_musician') }}</option>
            <option value="agent">{{ __('ui.music.profile_tab_agent') }}</option>
            <option value="sound_engineer">{{ __('ui.music.profile_tab_sound_engineer') }}</option>
            <option value="arranger">{{ __('ui.music.profile_tab_arranger') }}</option>
            <option value="live_sound">{{ __('ui.music.profile_tab_live_sound') }}</option>
            <option value="lighting_designer">{{ __('ui.music.profile_tab_lighting_designer') }}</option>
            <option value="videographer">{{ __('ui.music.profile_tab_videographer') }}</option>
            <option value="photographer">{{ __('ui.music.profile_tab_photographer') }}</option>
            <option value="journalist">{{ __('ui.music.profile_tab_journalist') }}</option>
            <option value="venue_manager">{{ __('ui.music.profile_tab_venue_manager') }}</option>
            <option value="merchandiser">{{ __('ui.music.profile_tab_merchandiser') }}</option>
            <option value="tour_manager">{{ __('ui.music.profile_tab_tour_manager') }}</option>
            <option value="promoter">{{ __('ui.music.profile_tab_promoter') }}</option>
            <option value="recording_engineer">{{ __('ui.music.profile_tab_recording_engineer') }}</option>
            <option value="mastering_engineer">{{ __('ui.music.profile_tab_mastering_engineer') }}</option>
            <option value="session_producer">{{ __('ui.music.profile_tab_session_producer') }}</option>
            <option value="tech_rider">{{ __('ui.music.profile_tab_tech_rider') }}</option>
            <option value="backline_tech">{{ __('ui.music.profile_tab_backline_tech') }}</option>
            <option value="graphic_designer">{{ __('ui.music.profile_tab_graphic_designer') }}</option>
            <option value="smm_manager">{{ __('ui.music.profile_tab_smm_manager') }}</option>
            <option value="music_lawyer">{{ __('ui.music.profile_tab_music_lawyer') }}</option>
            <option value="accountant">{{ __('ui.music.profile_tab_accountant') }}</option>
        </select>
    </div>
    <p class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/80 dark:text-zinc-200">
        {{ $profileDescription }}
    </p>

    <div tabindex="0">
        @if ($tab === 'musician')
            <livewire:music.musician-profile-page wire:key="profile-musician" />
        @elseif ($tab === 'teacher')
            <livewire:music.teacher-profile-page wire:key="profile-teacher" />
        @elseif ($tab === 'organizer')
            <livewire:music.music-organizer-profile-page wire:key="profile-organizer" />
        @elseif ($tab === 'manager')
            <livewire:music.music-manager-profile-page wire:key="profile-manager" />
        @elseif ($tab === 'session_musician')
            <livewire:music.music-session-musician-profile-page wire:key="profile-session-musician" />
        @else
            <livewire:music.music-generic-profile-page :profile="$tab" :key="'profile-generic-'.$tab" />
        @endif
    </div>
</div>
