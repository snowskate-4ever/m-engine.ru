<div class="mx-auto w-full max-w-3xl space-y-8">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="lg">{{ $recordId ? __('ui.music.performer_edit') : __('ui.music.performer_create') }}</flux:heading>

        @if ($record)
            <flux:callout variant="secondary">
                <div class="text-sm">
                    {{ __('ui.music.matching_progress') }}:
                    {{ __('ui.music.matching_open_requests') }} — {{ $matchingProgress['open_requests'] }},
                    {{ __('ui.music.matching_incomplete_events') }} — {{ $matchingProgress['incomplete_events'] }},
                    {{ __('ui.music.matching_ready_events') }} — {{ $matchingProgress['ready_events'] }}.
                </div>
            </flux:callout>
        @endif

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

            <flux:field>
                <flux:label>{{ __('ui.music.fields.performer_kind') }}</flux:label>
                <select
                    wire:model="performer_kind"
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    @foreach ($performerKinds as $kind)
                        <option value="{{ $kind->value }}">{{ __('ui.music.performer_kind.' . $kind->value) }}</option>
                    @endforeach
                </select>
                <flux:error name="performer_kind" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.fields.slug') }}</flux:label>
                <flux:input wire:model="slug" type="text" />
                <flux:description>{{ __('ui.music.fields.slug_hint') }}</flux:description>
                <flux:error name="slug" />
            </flux:field>

            <flux:checkbox wire:model="public_page_enabled" :label="__('ui.music.fields.public_enabled')" />

            @if ($record && filled($slug))
                <flux:callout variant="secondary" inline>
                    <a href="{{ route('public.performers.show', ['slug' => $slug]) }}" target="_blank" rel="noopener" class="font-medium underline underline-offset-2">
                        {{ __('ui.music.open_public_page') }}
                    </a>
                </flux:callout>
            @endif

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
    </div>

    @if ($record)
        <livewire:music.performer-lineup-panel :peformer-id="$record->id" :key="'lineup-'.$record->id" />
        <livewire:music.social-links-panel owner-kind="performer" :owner-id="$record->id" :key="'socials-performer-'.$record->id" />
        <livewire:music.address-book-panel owner-kind="performer" :owner-id="$record->id" :key="'addresses-performer-'.$record->id" />
    @endif
</div>
