@php
    $outerClass = $embedded
        ? 'flex h-full min-h-0 w-full min-w-0 flex-1 flex-col'
        : 'flex w-full max-w-3xl flex-col gap-4';
@endphp
<div class="{{ $outerClass }}"
     wire:key="messenger-room-poll-{{ $pollIntervalSeconds }}-{{ $aiWaitingForReply ? 'w' : 'i' }}"
     wire:poll.keep-alive.{{ $pollIntervalSeconds }}s="loadMessages">
    @if ($embedded)
        {{-- Шапка как у external-chat-window в CRM --}}
        <div class="shrink-0 border-b border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $headerTitle }}
                    </h2>
                    @if ($isAiChat)
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('ui.messenger.ai_chat') }}
                        </p>
                    @elseif (($headerMeta['type'] ?? '') === 'group' && ! empty($headerMeta['participants']))
                        @php
                            $allNames = collect($headerMeta['participants'])->pluck('name')->filter();
                            $namesPreview = $allNames->take(12);
                        @endphp
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('ui.messenger.group') }} · {{ $namesPreview->implode(', ') }}{{ $allNames->count() > 12 ? '…' : '' }}
                        </p>
                    @elseif (($headerMeta['type'] ?? '') === 'direct' && ! empty($headerMeta['direct_peer']['id']))
                        <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            <span>{{ __('ui.messenger.direct') }}</span>
                            @if (! empty($headerMeta['direct_peer']['name']))
                                <span class="ms-1">· {{ $headerMeta['direct_peer']['name'] }}</span>
                            @endif
                            @if (array_key_exists('is_online', $headerMeta) && $headerMeta['is_online'] !== null)
                                <span class="ms-1">· {{ $headerMeta['is_online'] ? __('ui.online') : __('ui.offline') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                {{-- Действия только в меню ⋮ (без отдельных кнопок в шапке) --}}
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <flux:dropdown position="bottom" align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" :title="__('ui.messenger.chat_menu_title')" />
                        <flux:menu class="min-w-[14rem]">
                            <flux:menu.item :href="route('messenger.settings.notifications')" icon="bell" wire:navigate>
                                {{ __('ui.messenger.notifications_title') }}
                            </flux:menu.item>
                            <flux:menu.item as="button" type="button" wire:click="toggleMute" icon="{{ $muted ? 'bell-alert' : 'bell-slash' }}">
                                {{ $muted ? __('ui.messenger.unmute_chat') : __('ui.messenger.mute_chat') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    @else
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:link href="{{ route('messenger.index') }}" wire:navigate class="text-sm">{{ __('ui.back') }}</flux:link>
            <flux:button size="sm" variant="ghost" wire:click="toggleMute" type="button">
                {{ $muted ? __('ui.messenger.unmute_chat') : __('ui.messenger.mute_chat') }}
            </flux:button>
        </div>
    @endif

    {{-- Лента сообщений: разметка как в CRM (время слева узкой колонкой, пузыри) --}}
    @if ($embedded)
        <div
            class="min-h-0 flex-1 overflow-y-auto overscroll-y-none p-4"
            id="messages-container"
            x-data="{
                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$el;
                        container.scrollTop = container.scrollHeight;
                    });
                },
                isNearBottom() {
                    const container = this.$el;
                    const threshold = 100;
                    return container.scrollHeight - container.scrollTop - container.clientHeight < threshold;
                }
            }"
            x-init="scrollToBottom()"
            @messages-updated.window="scrollToBottom()"
        >
            @if ($hasMoreOlder && $nextBeforeId)
                <div class="mb-4 text-center">
                    <button
                        type="button"
                        wire:click="loadOlderMessages"
                        wire:loading.attr="disabled"
                        class="text-sm text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        {{ __('ui.messenger.load_older') }}
                    </button>
                </div>
            @endif

            @include('livewire.messenger.partials.message-list-items')

            @if ($items === [])
                <p class="py-4 text-center text-sm text-zinc-500">{{ __('ui.messenger.no_messages') }}</p>
            @endif
        </div>
    @else
        @if ($hasMoreOlder && $nextBeforeId)
            <div class="shrink-0 px-4 pt-2">
                <flux:button size="sm" variant="ghost" wire:click="loadOlderMessages" type="button">
                    {{ __('ui.messenger.load_older') }}
                </flux:button>
            </div>
        @endif
        <div class="flex max-h-[50vh] min-h-[200px] flex-1 flex-col overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div
                class="flex flex-1 flex-col gap-1 overflow-y-auto overscroll-y-none p-3"
                id="messages-container"
                x-data="{
                    scrollToBottom() {
                        this.$nextTick(() => {
                            const container = this.$el;
                            container.scrollTop = container.scrollHeight;
                        });
                    }
                }"
                x-init="scrollToBottom()"
                @messages-updated.window="scrollToBottom()"
            >
                @include('livewire.messenger.partials.message-list-items')
                @if ($items === [])
                    <p class="text-center text-sm text-zinc-500">{{ __('ui.messenger.no_messages') }}</p>
                @endif
            </div>
        </div>
    @endif

    @if ($isAiChat && $aiWaitingForReply)
        <p class="shrink-0 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400" wire:loading.remove wire:target="send">
            {{ __('ui.messenger.ai_thinking') }}
        </p>
    @endif

    @if ($isAiChat && config('ai.enabled'))
        <div class="shrink-0 space-y-3 border-t border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('ui.messenger.ai_skills') }}</flux:heading>

            @if ($editingSkillId)
                <form wire:submit="saveSkillEdit" class="space-y-3">
                    <flux:input wire:model="skillTitle" type="text" :label="__('ui.messenger.skill_title_field')" />
                    <flux:textarea wire:model="skillInstruction" rows="4" :label="__('ui.messenger.skill_instruction_field')" />
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="primary" type="submit" square :title="__('ui.save')" icon="save-floppy" />
                        <flux:button variant="ghost" type="button" wire:click="cancelSkillEdit" square icon="cancel-play" :title="__('ui.cancel')" />
                    </div>
                </form>
            @else
                <form wire:submit="addSkill" class="space-y-3">
                    <flux:input wire:model="skillTitle" type="text" :label="__('ui.messenger.skill_title_field')" />
                    <flux:textarea wire:model="skillInstruction" rows="3" :label="__('ui.messenger.skill_instruction_field')" />
                    <flux:button variant="ghost" type="submit" size="sm">{{ __('ui.messenger.skill_add') }}</flux:button>
                </form>
            @endif

            @if ($skills !== [])
                <ul class="space-y-2 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-600">
                    @foreach ($skills as $s)
                        <li class="flex flex-col gap-1 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-900/50">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $s['title'] }}</span>
                                @if (! empty($s['owned']))
                                    <div class="flex flex-wrap gap-1">
                                        <flux:button size="xs" variant="ghost" type="button" wire:click="toggleSkillEnabled({{ (int) $s['id'] }})">
                                            {{ ! empty($s['enabled']) ? __('ui.messenger.skill_disable') : __('ui.messenger.skill_enable') }}
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" type="button" wire:click="startEditSkill({{ (int) $s['id'] }})">
                                            {{ __('ui.edit') }}
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" type="button" wire:click="deleteSkill({{ (int) $s['id'] }})" wire:confirm="{{ __('ui.confirm_message') }}">
                                            {{ __('ui.delete') }}
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                            @if (empty($s['enabled']))
                                <span class="text-xs text-amber-700 dark:text-amber-400">{{ __('ui.messenger.skill_disabled_badge') }}</span>
                            @endif
                            <p class="whitespace-pre-wrap text-xs text-zinc-600 dark:text-zinc-400">{{ $s['instruction_text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <form wire:submit="send" class="shrink-0 space-y-3 border-t border-zinc-200 p-4 dark:border-zinc-700">
        <flux:textarea wire:model="body" rows="{{ $embedded ? 2 : 3 }}" :placeholder="__('ui.messenger.message_placeholder')" />
        <div class="flex flex-wrap items-end justify-end gap-3">
            @if (! $isAiChat)
                <label
                    class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-zinc-600 outline-none transition-colors hover:bg-zinc-100 focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:focus-visible:ring-zinc-500 dark:focus-visible:ring-offset-zinc-900"
                    title="{{ __('ui.messenger.attach_file') }}"
                >
                    <span class="sr-only">{{ __('ui.messenger.attach_file') }}</span>
                    <input type="file" wire:model="attachment" class="sr-only" />
                    <flux:icon.paper-clip variant="outline" class="size-5" />
                </label>
            @endif
            <flux:button
                variant="primary"
                type="submit"
                icon="paper-airplane"
                wire:loading.attr="disabled"
                :title="__('ui.messenger.send')"
            />
        </div>
        @if (! $isAiChat)
            <div wire:loading wire:target="attachment" class="text-end text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.loading') }}</div>
        @endif
    </form>

    @script
    <script>
        const cid = @json($conversation->id);

        if (window.Echo) {
            const channel = window.Echo.private('messenger.conversation.' + cid);
            channel.listen('.messenger.message.sent', () => $wire.loadMessages());
            channel.listen('.messenger.read.updated', () => $wire.loadMessages());
            channel.listen('.messenger.conversation.updated', () => $wire.loadMessages());
        }
    </script>
    @endscript
</div>
