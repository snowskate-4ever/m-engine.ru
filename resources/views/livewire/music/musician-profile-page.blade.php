<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ __('ui.music.musician_card') }}</flux:heading>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:description>{{ __('ui.music.profile_musician_hint') }}</flux:description>
            <flux:button type="button" wire:click="toggleProfile" variant="{{ $enabled ? 'filled' : 'primary' }}">
                {{ $enabled ? __('ui.music.profile_disable') : __('ui.music.profile_enable') }}
            </flux:button>
        </div>

        @if (! $enabled)
            <flux:callout variant="warning">{{ __('ui.music.profile_enable_required') }}</flux:callout>
        @else
        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('ui.music.fields.name') }}</flux:label>
                <flux:input wire:model="name" type="text" autocomplete="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.description') }}</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.bio') }}</flux:label>
                <flux:textarea wire:model="bio" rows="4" />
                <flux:error name="bio" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.slug') }}</flux:label>
                <flux:input wire:model="slug" type="text" placeholder="ivan-ivanov" />
                <flux:description>{{ __('ui.music.fields.slug_hint') }}</flux:description>
                <flux:error name="slug" />
            </flux:field>

            <flux:field>
                <flux:checkbox wire:model="public_page_enabled" :label="__('ui.music.fields.public_enabled')" />
                <flux:error name="public_page_enabled" />
            </flux:field>

            @if ($record && filled($slug))
                <flux:callout variant="secondary" inline>
                    <a href="{{ route('public.musicians.show', ['slug' => $slug]) }}" target="_blank" rel="noopener" class="font-medium underline underline-offset-2">
                        {{ __('ui.music.open_public_page') }}
                    </a>
                </flux:callout>
            @endif

            <flux:field>
                <flux:label>{{ __('ui.music.fields.instruments') }}</flux:label>
                <div class="mt-2 flex flex-col gap-2">
                    @foreach ($instruments as $instrument)
                        <flux:checkbox wire:model="instrumentIds" :value="$instrument->id" :label="$instrument->name" />
                    @endforeach
                </div>
                <flux:error name="instrumentIds" />
            </flux:field>

            <flux:separator />

            <flux:heading size="md">{{ __('ui.music.layout_draft') }}</flux:heading>
            <flux:description>{{ __('ui.music.layout_draft_hint') }}</flux:description>
            <div class="flex flex-col gap-2">
                @foreach ($blockCatalog as $row)
                    <flux:checkbox wire:model="layoutBlockEnabled.{{ $row['id'] }}" :label="__($row['label_key'])" />
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button type="submit" variant="primary">{{ __('ui.save') }}</flux:button>
                @if ($record)
                    <flux:button type="button" wire:click="publishLayout" variant="filled">{{ __('ui.music.publish_layout') }}</flux:button>
                @endif
            </div>
        </form>
        @endif
    </div>

    @if ($record)
        <livewire:music.social-links-panel owner-kind="musician" :owner-id="$record->id" :key="'socials-musician-'.$record->id" />

        <div id="music-musician-lineup" class="scroll-mt-24 space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('ui.music.lineup_musician_section') }}</flux:heading>
            <flux:description>{{ __('ui.music.lineup_musician_section_hint') }}</flux:description>

            @if ($lineupNotice)
                <flux:callout variant="success">{{ $lineupNotice }}</flux:callout>
            @endif
            @if ($lineupError)
                <flux:callout variant="warning">{{ $lineupError }}</flux:callout>
            @endif

            @if ($pendingLineupInvites->isNotEmpty())
                <flux:heading size="md">{{ __('ui.music.lineup_invites_inbox') }}</flux:heading>
                <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($pendingLineupInvites as $p)
                        <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->name }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <flux:button type="button" size="sm" variant="primary" wire:click="acceptLineupInvite({{ $p->id }})">
                                    {{ __('ui.music.lineup_accept') }}
                                </flux:button>
                                <flux:button type="button" size="sm" variant="ghost" wire:click="declineLineupInvite({{ $p->id }})">
                                    {{ __('ui.music.lineup_decline') }}
                                </flux:button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($acceptedLineup->isNotEmpty())
                <flux:heading size="md">{{ __('ui.music.lineup_my_bands') }}</flux:heading>
                <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($acceptedLineup as $p)
                        <li class="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->name }}</p>
                                <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <input
                                        type="checkbox"
                                        class="rounded border-zinc-300 text-zinc-900 focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:ring-zinc-400/30"
                                        @checked($p->pivot->show_on_musician_profile)
                                        wire:change="setLineupShowOnProfile($event.target.checked, {{ $p->id }})"
                                    />
                                    <span>{{ __('ui.music.lineup_show_on_profile') }}</span>
                                </label>
                            </div>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="leaveLineup({{ $p->id }})" wire:confirm="{{ __('ui.music.lineup_leave_confirm') }}">
                                {{ __('ui.music.lineup_leave') }}
                            </flux:button>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($pendingLineupInvites->isEmpty() && $acceptedLineup->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.lineup_musician_empty') }}</p>
            @endif
        </div>

        <livewire:music.address-book-panel owner-kind="musician" :owner-id="$record->id" :key="'addresses-musician-'.$record->id" />
    @endif
</div>
