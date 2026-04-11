<div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="md">{{ __('ui.music.profile_venue_representative_title') }}</flux:heading>
    <flux:description>{{ __('ui.music.profile_venue_representative_hint') }}</flux:description>
    <flux:button type="button" wire:click="toggle" variant="{{ $enabled ? 'filled' : 'primary' }}">
        {{ $enabled ? __('ui.music.profile_disable') : __('ui.music.profile_enable') }}
    </flux:button>

    <flux:separator />
    <flux:heading size="sm">{{ __('ui.music.profile_venue_memberships') }}</flux:heading>
    @forelse ($memberships as $membership)
        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
            <div class="font-medium">{{ $membership->entity?->name ?? '#'.$membership->entity_id }}</div>
            <div class="text-zinc-500">{{ __('ui.music.membership_status.'.$membership->status->value) }}</div>
            @if ($membership->status->value === 'pending')
                <div class="mt-2 flex gap-2">
                    <flux:button type="button" variant="primary" wire:click="acceptMembership({{ $membership->id }})">{{ __('ui.music.lineup_accept') }}</flux:button>
                    <flux:button type="button" variant="ghost" wire:click="declineMembership({{ $membership->id }})">{{ __('ui.music.lineup_decline') }}</flux:button>
                </div>
            @endif
        </div>
    @empty
        <div class="text-sm text-zinc-500">{{ __('ui.notfound') }}</div>
    @endforelse

    @if (auth()->check())
        <flux:separator />
        <livewire:music.social-links-panel owner-kind="user" :owner-id="auth()->id()" :key="'socials-venue-representative-'.auth()->id()" />
    @endif
</div>
