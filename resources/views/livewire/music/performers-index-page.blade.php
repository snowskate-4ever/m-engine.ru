<div class="mx-auto w-full max-w-3xl space-y-4">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <flux:modal wire:model="showCreateModal" name="performers-create" class="w-full max-w-xl">
        <flux:heading size="lg">{{ __('ui.music.performer_create') }}</flux:heading>

        <form wire:submit="createPerformer" class="mt-4 grid gap-4">
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
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" wire:click="closeCreateModal" square icon="cancel-play" :title="__('ui.cancel')" />
            <flux:button type="button" variant="primary" wire:click="createPerformer" square icon="plus" :title="__('ui.create')" />
        </div>
    </flux:modal>

    <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($performers as $p)
            <li class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $p->name }}</div>
                    @if (filled($p->slug))
                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ url('/performers/'.$p->slug) }}</div>
                    @endif
                </div>
                <flux:button size="sm" :href="route('music.performers.edit', $p)" wire:navigate>{{ __('ui.edit') }}</flux:button>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.performers_empty') }}</li>
        @endforelse
    </ul>
</div>
