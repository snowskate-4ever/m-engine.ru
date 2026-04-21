<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ $recordId ? __('ui.music.'.$routePrefix.'_edit') : __('ui.music.'.$routePrefix.'_create') }}</flux:heading>

        @if ($record && in_array($kind, ['concert_venue', 'studio', 'rehearsal', 'school'], true))
            <flux:callout variant="secondary">
                <div class="text-sm">
                    {{ __('ui.music.matching_progress') }}:
                    {{ __('ui.music.matching_open_requests') }} — {{ $matchingProgress['open_requests'] }},
                    {{ __('ui.music.matching_incomplete_events') }} — {{ $matchingProgress['incomplete_events'] }},
                    {{ __('ui.music.matching_ready_events') }} — {{ $matchingProgress['ready_events'] }}.
                </div>
            </flux:callout>
        @endif

        <div x-data="{ layoutSettingsOpen: false }">
        <form wire:submit="save" class="space-y-4">
            <flux:field>
                <flux:label>{{ __('ui.music.fields.name') }}</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.description') }}</flux:label>
                <flux:textarea wire:model="description" rows="4" />
                <flux:error name="description" />
            </flux:field>

            @if ($kind === 'shop' && $record)
                <flux:callout variant="secondary" inline class="flex flex-wrap gap-x-4 gap-y-1">
                    <a href="{{ route('music.shops.inventory', $record) }}" class="font-medium underline underline-offset-2" wire:navigate>
                        {{ __('ui.music.shop_open_inventory') }}
                    </a>
                    <a href="{{ route('music.shops.orders', $record) }}" class="font-medium underline underline-offset-2" wire:navigate>
                        {{ __('ui.music.shop_open_orders') }}
                    </a>
                </flux:callout>
            @endif

            <flux:separator />

            <flux:heading size="md">{{ __('ui.music.legal_section') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.legal_entity_type') }}</flux:label>
                <select
                    wire:model="legal_entity_type"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="">{{ __('ui.optional') }}</option>
                    <option value="individual">{{ __('ui.music.legal_individual') }}</option>
                    <option value="legal_person">{{ __('ui.music.legal_person') }}</option>
                </select>
                <flux:error name="legal_entity_type" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.company_name') }}</flux:label>
                <flux:input wire:model="company_name" type="text" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.inn') }}</flux:label>
                <flux:input wire:model="inn" type="text" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.ogrn') }}</flux:label>
                <flux:input wire:model="ogrn" type="text" />
            </flux:field>

            <div class="flex flex-wrap gap-3 pt-2">
                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
                <flux:button type="button" variant="ghost" @click="layoutSettingsOpen = true">{{ __('ui.music.layout_draft') }}</flux:button>
                @if ($record)
                    <flux:button type="button" wire:click="publishLayout" variant="filled">{{ __('ui.music.publish_layout') }}</flux:button>
                @endif
            </div>
        </form>
        <div
            x-show="layoutSettingsOpen"
            x-transition.opacity
            x-cloak
            class="fixed inset-0 z-[120] flex items-center justify-center bg-zinc-900/50 p-4"
            @click.self="layoutSettingsOpen = false"
            @keydown.escape.window="layoutSettingsOpen = false"
        >
            <div class="w-full max-w-2xl rounded-xl border border-zinc-200 bg-white p-5 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('ui.music.layout_draft') }}</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('ui.music.layout_draft_hint') }}</p>
                    </div>
                    <flux:button type="button" size="sm" variant="ghost" @click="layoutSettingsOpen = false">
                        {{ __('ui.close') }}
                    </flux:button>
                </div>
                <div class="flex flex-col gap-2">
                    @foreach ($blockCatalog as $row)
                        <label class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-800 dark:border-zinc-700 dark:text-zinc-100">
                            <span>{{ __($row['label_key']) }}</span>
                            <input
                                type="checkbox"
                                wire:model="layoutBlockEnabled.{{ $row['id'] }}"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:ring-zinc-400/30"
                            />
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        </div>
    </div>

    @if ($record)
        @if ($kind === 'concert_venue' && (int) ($record->owner_user_id ?? 0) === (int) auth()->id())
            <livewire:music.venue-representatives-panel :venue-id="$record->id" :key="'venue-representatives-'.$record->id" />
        @endif
    @endif
</div>
