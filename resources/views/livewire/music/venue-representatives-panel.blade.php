<div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="md">{{ __('ui.music.venue_representatives_title') }}</flux:heading>
    <flux:description>{{ __('ui.music.venue_representatives_hint') }}</flux:description>

    <div class="flex flex-wrap items-end gap-3">
        <flux:field>
            <flux:label>{{ __('ui.music.venue_representative_user_id') }}</flux:label>
            <flux:input wire:model="memberUserId" type="number" min="1" />
            <flux:error name="memberUserId" />
        </flux:field>
        <flux:button type="button" wire:click="invite" variant="primary">{{ __('ui.create') }}</flux:button>
    </div>

    @forelse ($memberships as $membership)
        <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
            <div class="font-medium">{{ $membership->member?->name ?? ('#'.$membership->member_user_id) }}</div>
            <div class="text-zinc-500">{{ __('ui.music.membership_status.'.$membership->status->value) }}</div>
            @if ($membership->status->value !== 'revoked')
                <div class="mt-2">
                    <flux:button type="button" variant="ghost" wire:click="revoke({{ $membership->id }})">{{ __('ui.delete') }}</flux:button>
                </div>
            @endif
        </div>
    @empty
        <div class="text-sm text-zinc-500">{{ __('ui.notfound') }}</div>
    @endforelse
</div>
