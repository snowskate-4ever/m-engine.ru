<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.profile_manager_title') }}</flux:heading>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:description>{{ __('ui.music.profile_manager_hint') }}</flux:description>
        </div>

        @if ($enabled)
            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button
                    type="button"
                    wire:click="toggle"
                    variant="ghost"
                    size="sm"
                    icon="x-mark"
                    wire:loading.attr="disabled"
                    wire:target="toggle"
                    :title="__('ui.music.profile_disable')"
                    :aria-label="__('ui.music.profile_disable')"
                />
            </div>
        @else
            <flux:callout variant="secondary">{{ __('ui.music.profile_generic_criteria_disabled') }}</flux:callout>
            <flux:button
                type="button"
                wire:click="toggle"
                variant="primary"
                icon="power"
                wire:loading.attr="disabled"
                wire:target="toggle"
                :title="__('ui.music.profile_enable')"
                :aria-label="__('ui.music.profile_enable')"
            />
        @endif
    </div>

    <livewire:music.music-user-json-criteria-form
        wire:key="manager-criteria-{{ $criteriaProfileKey }}"
        :profile-key="$criteriaProfileKey"
        :enabled="$enabled"
    />

    <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="md">{{ __('ui.music.profile_manager_memberships') }}</flux:heading>
        @forelse ($memberships as $membership)
            <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                <div class="font-medium">{{ $membership->entity?->name ?? '#'.$membership->entity_id }}</div>
                <div class="text-zinc-500">{{ __('ui.music.membership_status.'.$membership->status->value) }}</div>
            </div>
        @empty
            <div class="text-sm text-zinc-500">{{ __('ui.notfound') }}</div>
        @endforelse
    </div>

</div>
