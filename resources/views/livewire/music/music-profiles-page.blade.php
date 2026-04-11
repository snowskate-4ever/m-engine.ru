<div class="mx-auto w-full max-w-3xl space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="sm">{{ __('ui.music.active_actor_title') }}</flux:heading>
        <div class="flex flex-wrap items-end gap-3">
            <flux:field class="min-w-[16rem] flex-1">
                <flux:label>{{ __('ui.music.active_actor_label') }}</flux:label>
                <select wire:model="activeActorRef" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="">{{ __('ui.select') }}</option>
                    @foreach ($actorOptions as $option)
                        <option value="{{ $option['type'] }}:{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </flux:field>
            <flux:button type="button" wire:click="saveActiveActor" variant="primary">{{ __('ui.save') }}</flux:button>
        </div>
    </div>

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
        <button
            type="button"
            role="tab"
            id="music-profile-tab-organizer"
            aria-selected="{{ $tab === 'organizer' ? 'true' : 'false' }}"
            tabindex="{{ $tab === 'organizer' ? '0' : '-1' }}"
            wire:click="$set('tab', 'organizer')"
            @class([
                'min-w-[8rem] flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100' => $tab === 'organizer',
                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== 'organizer',
            ])
        >
            {{ __('ui.music.profile_tab_organizer') }}
        </button>
        <button
            type="button"
            role="tab"
            id="music-profile-tab-venue-representative"
            aria-selected="{{ $tab === 'venue_representative' ? 'true' : 'false' }}"
            tabindex="{{ $tab === 'venue_representative' ? '0' : '-1' }}"
            wire:click="$set('tab', 'venue_representative')"
            @class([
                'min-w-[8rem] flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100' => $tab === 'venue_representative',
                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== 'venue_representative',
            ])
        >
            {{ __('ui.music.profile_tab_venue_representative') }}
        </button>
        <button
            type="button"
            role="tab"
            id="music-profile-tab-manager"
            aria-selected="{{ $tab === 'manager' ? 'true' : 'false' }}"
            tabindex="{{ $tab === 'manager' ? '0' : '-1' }}"
            wire:click="$set('tab', 'manager')"
            @class([
                'min-w-[8rem] flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition',
                'bg-white text-zinc-900 shadow-xs dark:bg-zinc-900 dark:text-zinc-100' => $tab === 'manager',
                'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => $tab !== 'manager',
            ])
        >
            {{ __('ui.music.profile_tab_manager') }}
        </button>
    </div>

    <div role="tabpanel" aria-labelledby="music-profile-tab-{{ $tab }}" tabindex="0">
        @if ($tab === 'musician')
            <livewire:music.musician-profile-page wire:key="profile-musician" />
        @elseif ($tab === 'teacher')
            <livewire:music.teacher-profile-page wire:key="profile-teacher" />
        @elseif ($tab === 'organizer')
            <livewire:music.music-organizer-profile-page wire:key="profile-organizer" />
        @elseif ($tab === 'venue_representative')
            <livewire:music.music-venue-representative-profile-page wire:key="profile-venue-representative" />
        @else
            <livewire:music.music-manager-profile-page wire:key="profile-manager" />
        @endif
    </div>
</div>
