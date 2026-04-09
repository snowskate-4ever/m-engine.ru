<div class="mx-auto w-full max-w-3xl space-y-6">
    <div
        role="tablist"
        aria-label="{{ __('ui.music.profiles_tabs_label') }}"
        class="flex flex-wrap gap-1 rounded-xl border border-zinc-200 bg-zinc-100 p-1 dark:border-zinc-700 dark:bg-zinc-800/60"
    >
        <button
            type="button"
            role="tab"
            id="music-profile-tab-musician"
            aria-selected="{{ $tab === 'musician' ? 'true' : 'false' }}"
            tabindex="{{ $tab === 'musician' ? '0' : '-1' }}"
            wire:click="$set('tab', 'musician')"
            @class([
                'min-w-[8rem] flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100' => $tab === 'musician',
                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== 'musician',
            ])
        >
            {{ __('ui.public_profile.type_musician') }}
        </button>
        <button
            type="button"
            role="tab"
            id="music-profile-tab-teacher"
            aria-selected="{{ $tab === 'teacher' ? 'true' : 'false' }}"
            tabindex="{{ $tab === 'teacher' ? '0' : '-1' }}"
            wire:click="$set('tab', 'teacher')"
            @class([
                'min-w-[8rem] flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100' => $tab === 'teacher',
                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== 'teacher',
            ])
        >
            {{ __('ui.public_profile.type_teacher') }}
        </button>
    </div>

    <div role="tabpanel" aria-labelledby="music-profile-tab-{{ $tab }}" tabindex="0">
        @if ($tab === 'musician')
            <livewire:music.musician-profile-page wire:key="profile-musician" />
        @else
            <livewire:music.teacher-profile-page wire:key="profile-teacher" />
        @endif
    </div>
</div>
