<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.profile_organizer_title') }}</flux:heading>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:description>{{ __('ui.music.profile_organizer_hint') }}</flux:description>
            <flux:button type="button" wire:click="toggle" variant="{{ $enabled ? 'filled' : 'primary' }}">
                {{ $enabled ? __('ui.music.profile_disable') : __('ui.music.profile_enable') }}
            </flux:button>
        </div>

        @if (! $enabled)
            <flux:callout variant="warning">{{ __('ui.music.profile_enable_required') }}</flux:callout>
        @endif
    </div>

    @if (auth()->check())
        <livewire:music.social-links-panel owner-kind="user" :owner-id="auth()->id()" :key="'socials-organizer-'.auth()->id()" />
    @endif
</div>
