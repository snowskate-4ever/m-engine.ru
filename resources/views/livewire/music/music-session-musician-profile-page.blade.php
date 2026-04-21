<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.profile_session_musician_title') }}</flux:heading>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:description>{{ __('ui.music.profile_session_musician_hint') }}</flux:description>
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

    @if ($enabled)
        <flux:callout variant="secondary">
            <div class="space-y-2">
                <p>{{ __('ui.music.profile_session_musician_criteria_hint') }}</p>
                <div>
                    <a
                        href="{{ route('music.profiles', ['tab' => 'musician']) }}"
                        class="text-sm font-medium text-blue-600 underline decoration-blue-600/30 underline-offset-2 hover:decoration-blue-600 dark:text-blue-400 dark:decoration-blue-400/30 dark:hover:decoration-blue-400"
                        wire:navigate
                    >{{ __('ui.music.profile_session_musician_edit_musician_tab') }}</a>
                </div>
            </div>
        </flux:callout>
    @endif
</div>
