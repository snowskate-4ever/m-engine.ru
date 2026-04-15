<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.profile_organizer_title') }}</flux:heading>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:description>{{ __('ui.music.profile_organizer_hint') }}</flux:description>
        </div>

        @if ($enabled)
            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button type="button" wire:click="toggle" variant="ghost" size="sm">{{ __('ui.music.profile_disable') }}</flux:button>
            </div>
        @else
            <flux:callout variant="secondary">{{ __('ui.music.profile_organizer_criteria_disabled') }}</flux:callout>
            <flux:button type="button" wire:click="toggle" variant="primary">{{ __('ui.music.profile_enable') }}</flux:button>
        @endif
    </div>

    <livewire:music.music-user-json-criteria-form
        wire:key="organizer-criteria-{{ $criteriaProfileKey }}"
        :profile-key="$criteriaProfileKey"
        :enabled="$enabled"
    />
</div>
