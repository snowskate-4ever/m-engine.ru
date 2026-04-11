@php
    use App\Enums\PerformerMembershipStatus;
@endphp

<div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="lg">{{ __('ui.music.lineup_section') }}</flux:heading>
    <flux:description>{{ __('ui.music.lineup_section_hint') }}</flux:description>

    @if ($notice)
        <flux:callout variant="success">{{ $notice }}</flux:callout>
    @endif

    <form wire:submit="invite" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <flux:field class="min-w-0 flex-1">
            <flux:label>{{ __('ui.music.lineup_invite_label') }}</flux:label>
            <select
                wire:model="inviteMusicianId"
                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
            >
                <option value="">{{ __('ui.select') }}</option>
                @foreach ($inviteOptions as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
            <flux:error name="inviteMusicianId" />
        </flux:field>
        <flux:button type="submit" variant="primary">{{ __('ui.music.lineup_invite_button') }}</flux:button>
    </form>

    @if ($inviteOptions->isEmpty())
        <flux:callout variant="secondary">{{ __('ui.music.lineup_no_musicians_to_invite') }}</flux:callout>
    @endif

    <flux:separator />

    <div class="space-y-4">
        <flux:heading size="md">{{ __('ui.music.lineup_roster') }}</flux:heading>

        @php
            $byStatus = $peformer->musicians->groupBy(function ($m) {
                $s = $m->pivot->status;

                return $s instanceof PerformerMembershipStatus ? $s->value : (string) $s;
            });
        @endphp

        @foreach ([PerformerMembershipStatus::Pending, PerformerMembershipStatus::Accepted, PerformerMembershipStatus::Declined, PerformerMembershipStatus::Left] as $st)
            @php $group = $byStatus->get($st->value, collect()); @endphp
            @if ($group->isNotEmpty())
                <div class="space-y-2">
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.music.lineup_status.' . $st->value) }}</p>
                    <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                        @foreach ($group as $musician)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                                <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $musician->name }}</span>
                                <div class="flex flex-wrap gap-2">
                                    @if ($st === PerformerMembershipStatus::Pending)
                                        <flux:button type="button" size="sm" variant="ghost" wire:click="cancelInvite({{ $musician->id }})" wire:confirm="{{ __('ui.music.lineup_cancel_confirm') }}">
                                            {{ __('ui.music.lineup_cancel_invite') }}
                                        </flux:button>
                                    @endif
                                    @if ($st === PerformerMembershipStatus::Accepted)
                                        <flux:button type="button" size="sm" variant="ghost" wire:click="removeMember({{ $musician->id }})" wire:confirm="{{ __('ui.music.lineup_remove_confirm') }}">
                                            {{ __('ui.music.lineup_remove_member') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        @if ($peformer->musicians->isEmpty())
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.lineup_empty') }}</p>
        @endif
    </div>
</div>
