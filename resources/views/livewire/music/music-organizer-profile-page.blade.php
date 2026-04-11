<div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="md">{{ __('ui.music.profile_organizer_title') }}</flux:heading>
    <flux:description>{{ __('ui.music.profile_organizer_hint') }}</flux:description>
    <flux:button type="button" wire:click="toggle" variant="{{ $enabled ? 'filled' : 'primary' }}">
        {{ $enabled ? __('ui.music.profile_disable') : __('ui.music.profile_enable') }}
    </flux:button>

    @if (auth()->check())
        <flux:separator />
        <livewire:music.social-links-panel owner-kind="user" :owner-id="auth()->id()" :key="'socials-organizer-'.auth()->id()" />
    @endif
</div>
