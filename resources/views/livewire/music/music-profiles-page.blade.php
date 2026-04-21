<div class="mx-auto w-full max-w-3xl space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-100 p-3 dark:border-zinc-700 dark:bg-zinc-800/60">
        @if ($hasAnyEnabledProfile)
            <div>
                <label for="music-profile-enabled-select" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">
                    {{ __('ui.music.profiles_enabled_tabs_label') }}
                </label>
                <select
                    id="music-profile-enabled-select"
                    wire:model="quickSwitchTab"
                    wire:change="switchProfile($event.target.value)"
                    aria-label="{{ __('ui.music.profiles_enabled_tabs_label') }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-900 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    @foreach ($enabledTabOptions as $row)
                        <option value="{{ $row['value'] }}">{{ $row['label'] }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('ui.music.profiles_no_enabled_hint') }}</p>
        @endif
    </div>

    <div tabindex="0">
        @if ($tab === 'musician')
            <livewire:music.musician-profile-page wire:key="profile-musician-{{ $profileRequestVersion }}" :embedded-in-profiles-hub="true" />
        @elseif ($tab === 'teacher')
            <livewire:music.teacher-profile-page wire:key="profile-teacher-{{ $profileRequestVersion }}" />
        @elseif ($tab === 'organizer')
            <livewire:music.music-organizer-profile-page wire:key="profile-organizer-{{ $profileRequestVersion }}" />
        @elseif ($tab === 'manager')
            <livewire:music.music-manager-profile-page wire:key="profile-manager-{{ $profileRequestVersion }}" />
        @elseif ($tab === 'session_musician')
            <livewire:music.music-session-musician-profile-page wire:key="profile-session-musician-{{ $profileRequestVersion }}" />
        @else
            <livewire:music.music-generic-profile-page :profile="$tab" :key="'profile-generic-'.$tab.'-'.$profileRequestVersion" />
        @endif
    </div>
</div>
