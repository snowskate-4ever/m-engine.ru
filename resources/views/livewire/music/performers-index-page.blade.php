<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="lg">{{ __('ui.music.performers_index') }}</flux:heading>
        <flux:button :href="route('music.performers.create')" variant="primary" wire:navigate>{{ __('ui.music.performer_create') }}</flux:button>
    </div>

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
