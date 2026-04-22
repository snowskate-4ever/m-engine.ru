<div
    class="flex h-full min-h-0 w-full min-w-0 flex-1 flex-col bg-zinc-50 dark:bg-zinc-900"
    wire:poll.keep-alive.40s="refreshList"
>
    <div class="flex h-14 w-full shrink-0 flex-col items-center gap-1 py-2">
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
        <flux:button
            type="button"
            size="sm"
            variant="subtle"
            class="w-11 shrink-0 justify-center px-0"
            icon="plus"
            :title="__('ui.messenger.new_chat')"
            @click="Livewire.dispatch('messenger-open-new-chat')"
        />
        @foreach ($chats as $chat)
            @php
                $active = request()->routeIs('messenger.show')
                    && (int) optional(request()->route('conversation'))->id === (int) $chat['id'];
                $borderClass = ($chat['type'] ?? '') === 'direct'
                    ? 'border-zinc-400 dark:border-zinc-500'
                    : 'border-blue-400 dark:border-blue-500';
                $presenceShadowClass = '';
                if (($chat['type'] ?? '') === 'direct' || ($chat['is_support_chat'] ?? false)) {
                    $presenceShadowClass = ($chat['is_online'] ?? false)
                        ? 'shadow-[0_0_0_2px_rgba(34,197,94,0.35)] dark:shadow-[0_0_0_2px_rgba(34,197,94,0.5)]'
                        : 'shadow-[0_0_0_2px_rgba(239,68,68,0.35)] dark:shadow-[0_0_0_2px_rgba(239,68,68,0.5)]';
                }
            @endphp
            @if (request()->routeIs(['messenger.index', 'messenger.show']))
                <button
                    type="button"
                    wire:click="selectConversation({{ (int) $chat['id'] }})"
                    wire:key="rail-chat-{{ $chat['id'] }}"
                    title="{{ $chat['name'] }}"
                    @class([
                        'relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 bg-white text-xs font-semibold text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-zinc-100',
                        $borderClass,
                        $presenceShadowClass,
                        'ring-2 ring-blue-500 ring-offset-2 ring-offset-zinc-50 dark:ring-offset-zinc-900' => $active,
                    ])
                >
                    {{ $chat['initials'] }}
                    @if ($chat['unread_count'] > 0)
                        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-blue-500 px-0.5 text-[10px] font-bold text-white">
                            {{ $chat['unread_count'] > 9 ? '9+' : $chat['unread_count'] }}
                        </span>
                    @endif
                </button>
            @else
                <button
                    type="button"
                    wire:click="selectConversation({{ (int) $chat['id'] }})"
                    wire:key="rail-chat-{{ $chat['id'] }}"
                    title="{{ $chat['name'] }}"
                    @class([
                        'relative flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full border-2 bg-white text-xs font-semibold text-zinc-800 shadow-sm dark:bg-zinc-800 dark:text-zinc-100',
                        $borderClass,
                        $presenceShadowClass,
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

