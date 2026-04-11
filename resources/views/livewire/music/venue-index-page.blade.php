<div class="mx-auto w-full max-w-3xl">
    <ul class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($items as $item)
            <li class="flex items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</div>
                    @if (filled($item->slug))
                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ url('/'.$routePrefix.'/'.$item->slug) }}</div>
                    @endif
                </div>
                <flux:button size="sm" :href="route('music.'.$routePrefix.'.edit', $item)" wire:navigate>{{ __('ui.edit') }}</flux:button>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.music.'.$routePrefix.'_empty') }}</li>
        @endforelse
    </ul>
</div>
