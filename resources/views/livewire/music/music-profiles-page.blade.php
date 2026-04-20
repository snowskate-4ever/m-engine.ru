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
            @foreach ($tabOptions as $row)
                <option value="{{ $row['value'] }}">{{ $row['label'] }}</option>
            @endforeach
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
