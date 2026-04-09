<div class="flex min-h-0 min-w-0 flex-1 flex-col gap-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 flex-1 max-w-md">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('ui.notifications.filter_type') }}</label>
            <select
                wire:model.live="typeFilter"
                class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
            >
                <option value="">{{ __('ui.notifications.filter_all_types') }}</option>
                @foreach ($typeOptions as $fqcn)
                    <option value="{{ $fqcn }}">{{ \Illuminate\Support\Str::afterLast($fqcn, '\\') }}</option>
                @endforeach
            </select>
        </div>
        @if ($notifications->total() > 0 && auth()->user()->unreadNotifications()->exists())
            <flux:button variant="subtle" size="sm" wire:click="markAllRead" class="shrink-0">
                {{ __('ui.notifications.mark_all_read') }}
            </flux:button>
        @endif
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        @forelse ($notifications as $n)
            @php
                $row = $presenter->toPublicArray($n);
                $isUnread = $n->read_at === null;
                $href = $row['action_url'] ?? null;
            @endphp
            <div
                wire:key="notif-row-{{ $row['id'] }}"
                class="flex gap-3 border-b border-zinc-100 px-4 py-3 last:border-b-0 dark:border-zinc-800 {{ $isUnread ? 'bg-blue-50/50 dark:bg-blue-950/20' : '' }}"
            >
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['title'] ?? '' }}</div>
                    <div class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $row['body'] ?? '' }}</div>
                    @if ($n->created_at)
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">{{ $n->created_at->diffForHumans() }}</div>
                    @endif
                </div>
                <div class="flex shrink-0 flex-col items-end gap-2">
                    @if (filled($href))
                        <flux:button
                            size="sm"
                            variant="primary"
                            type="button"
                            wire:click="markReadAndOpen('{{ $row['id'] }}', @js($href))"
                        >
                            {{ __('ui.notifications.open') }}
                        </flux:button>
                    @endif
                    @if ($isUnread)
                        <flux:button size="xs" variant="ghost" type="button" wire:click="markRead('{{ $row['id'] }}')">
                            {{ __('ui.notifications.mark_read') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('ui.notifications.index_empty') }}
            </div>
        @endforelse
    </div>

    <div>
        {{ $notifications->links() }}
    </div>
</div>
