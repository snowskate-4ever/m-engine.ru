<div class="w-full max-w-xl space-y-6">
    <flux:link href="{{ route('messenger.index') }}" wire:navigate class="text-sm">{{ __('ui.back') }}</flux:link>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/80">
        <flux:heading size="md" class="mb-4">{{ __('ui.messenger.notifications_title') }}</flux:heading>
        <flux:checkbox wire:model.live="pushEnabled" :label="__('ui.messenger.push_label')" />
        <flux:text class="mt-3 text-sm text-zinc-500">
            {{ __('ui.messenger.push_hint') }}
        </flux:text>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/80">
        <flux:heading size="md" class="mb-4">{{ __('ui.messenger.music_email_section') }}</flux:heading>
        <flux:checkbox wire:model.live="musicLineupEmail" :label="__('ui.messenger.music_lineup_email_label')" />
        <flux:text class="mt-3 text-sm text-zinc-500">
            {{ __('ui.messenger.music_lineup_email_hint') }}
        </flux:text>
    </div>
</div>
