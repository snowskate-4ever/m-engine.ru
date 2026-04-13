<div
    class="flex h-full min-h-0 w-full min-w-0 flex-1 flex-col bg-zinc-50 dark:bg-zinc-900"
    wire:poll.keep-alive.40s="refreshList"
>
    <div class="flex h-14 w-full shrink-0 flex-col items-center gap-1 border-b border-zinc-200 py-2 dark:border-zinc-700">
        @if (request()->routeIs(['messenger.index', 'messenger.show']))
            <flux:button
                :href="route('messenger.index')"
                wire:navigate
                size="sm"
                variant="subtle"
                class="w-11 justify-center px-0"
                icon="chat-bubble-left-right"
                :title="__('ui.messenger.title')"
            />
        @else
            <flux:button
                type="button"
                size="sm"
                variant="subtle"
                class="w-11 justify-center px-0"
                icon="chat-bubble-left-right"
                :title="__('ui.messenger.title')"
                @click="$dispatch('toggle-messenger-float')"
            />
        @endif
    </div>

    <div class="flex min-h-0 flex-1 flex-col items-center gap-2 overflow-y-auto overscroll-contain py-2 pe-1 ps-1">
        @foreach ($chats as $chat)
            @php
                $active = request()->routeIs('messenger.show')
                    && (int) optional(request()->route('conversation'))->id === (int) $chat['id'];
                $borderClass = ($chat['type'] ?? '') === 'direct'
                    ? 'border-zinc-400 dark:border-zinc-500'
                    : 'border-blue-400 dark:border-blue-500';
            @endphp
            @if (request()->routeIs(['messenger.index', 'messenger.show']))
                <a
                    href="{{ route('messenger.show', $chat['id']) }}"
                    wire:navigate
                    wire:key="rail-chat-{{ $chat['id'] }}"
                    title="{{ $chat['name'] }}"
                    @class([
                        'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 bg-white text-xs font-semibold text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-zinc-100',
                        $borderClass,
                        'ring-2 ring-blue-500 ring-offset-2 ring-offset-zinc-50 dark:ring-offset-zinc-900' => $active,
                    ])
                >
                    {{ $chat['initials'] }}
                    @if ($chat['unread_count'] > 0)
                        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-blue-500 px-0.5 text-[10px] font-bold text-white">
                            {{ $chat['unread_count'] > 9 ? '9+' : $chat['unread_count'] }}
                        </span>
                    @endif
                </a>
            @else
                <button
                    type="button"
                    wire:key="rail-chat-{{ $chat['id'] }}"
                    title="{{ $chat['name'] }}"
                    @click="$dispatch('messenger-float-open-chat', { id: {{ (int) $chat['id'] }} })"
                    @class([
                        'relative flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full border-2 bg-white text-xs font-semibold text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-zinc-100',
                        $borderClass,
                    ])
                >
                    {{ $chat['initials'] }}
                    @if ($chat['unread_count'] > 0)
                        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-blue-500 px-0.5 text-[10px] font-bold text-white">
                            {{ $chat['unread_count'] > 9 ? '9+' : $chat['unread_count'] }}
                        </span>
                    @endif
                </button>
            @endif
        @endforeach
    </div>
</div>

@script
<script>
    (() => {
        const componentId = $wire.$id ?? '';
        const chatIds = Array.isArray($wire.chats)
            ? $wire.chats
                .map((row) => Number(row?.id ?? 0))
                .filter((id) => Number.isInteger(id) && id > 0)
            : [];

        if (!componentId) {
            return;
        }

        window.__messengerRailRealtime ??= {};

        const prevCleanup = window.__messengerRailRealtime[componentId];
        if (typeof prevCleanup === 'function') {
            prevCleanup();
        }

        if (!window.Echo || !Array.isArray(chatIds) || chatIds.length === 0) {
            window.__messengerRailRealtime[componentId] = null;
            return;
        }

        const channelNames = chatIds.map((id) => `messenger.conversation.${id}`);
        const refresh = () => $wire.refreshList();

        channelNames.forEach((name) => {
            window.Echo.private(name)
                .listen('.messenger.message.sent', refresh)
                .listen('.messenger.read.updated', refresh)
                .listen('.messenger.conversation.updated', refresh);
        });

        window.__messengerRailRealtime[componentId] = () => {
            channelNames.forEach((name) => window.Echo.leave(name));
            delete window.__messengerRailRealtime[componentId];
        };
    })();
</script>
@endscript
