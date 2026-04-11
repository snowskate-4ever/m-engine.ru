<div
    @class([
        'flex min-h-0 w-full max-w-full min-w-0 flex-col gap-4 pt-[10px]',
        'h-full max-h-full overflow-hidden' => $embedMode,
    ])
    wire:poll.keep-alive.20s="refreshList"
>
    <div class="flex shrink-0 flex-wrap items-center justify-between gap-3">
        <flux:link href="{{ route('messenger.settings.notifications') }}" wire:navigate class="ml-[15px] text-sm">
            {{ __('ui.messenger.notifications_title') }}
        </flux:link>
    </div>

    <div
        @class([
            'flex min-h-0 w-full min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-stretch',
            'lg:h-[calc(100vh-11rem)]' => ! $embedMode,
            'min-h-0 flex-1 overflow-hidden' => $embedMode,
        ])
    >
        {{-- Список чатов (как левая колонка внешнего чата в CRM) --}}
        <div class="flex max-h-[45vh] w-full shrink-0 flex-col overflow-hidden rounded-lg border border-zinc-200 lg:max-h-none lg:w-1/3 lg:min-w-[240px] lg:max-w-md dark:border-zinc-700">
            <div class="shrink-0 border-b border-zinc-200 p-3 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('ui.messenger.new_chat') }}</flux:heading>
                <form wire:submit="createChat" class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.type') }}</label>
                        <select wire:model.live="createType"
                                class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-900 outline-none ring-offset-2 focus:ring-2 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                            <option value="direct">{{ __('ui.messenger.direct') }}</option>
                            <option value="group">{{ __('ui.messenger.group') }}</option>
                            @if (config('ai.enabled'))
                                <option value="ai">{{ __('ui.messenger.ai_chat') }}</option>
                            @endif
                        </select>
                    </div>

                    @if ($createType === 'direct')
                        <flux:input wire:model="directUserId" type="number" min="1" :label="__('ui.messenger.peer_user_id')" />
                    @elseif ($createType === 'group')
                        <flux:input wire:model="groupTitle" type="text" :label="__('ui.messenger.group_title')" />
                        <flux:textarea wire:model="groupUserIds" rows="2" :label="__('ui.messenger.member_ids')" />
                    @elseif ($createType === 'ai')
                        <flux:input wire:model="aiTitle" type="text" :label="__('ui.messenger.ai_chat_title')" />
                        <div>
                            <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.ai_source') }}</label>
                            <select wire:model.live="aiSource"
                                    class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                <option value="server">{{ __('ui.messenger.ai_source_server') }}</option>
                                <option value="byok">{{ __('ui.messenger.ai_source_byok') }}</option>
                            </select>
                        </div>
                        @if ($aiSource === 'server')
                            @if (count($aiServerModels) === 0)
                                <p class="text-sm text-amber-700 dark:text-amber-400">{{ __('ui.messenger.ai_no_server_models') }}</p>
                            @else
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.ai_pick_model') }}</label>
                                    <select wire:model="aiServerModelId"
                                            class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                        <option value="">{{ __('ui.select') }}</option>
                                        @foreach ($aiServerModels as $opt)
                                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @else
                            @if (count($aiConnections) === 0)
                                <p class="text-sm text-amber-700 dark:text-amber-400">{{ __('ui.messenger.ai_no_connections') }}</p>
                            @else
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.ai_pick_connection') }}</label>
                                    <select wire:model="aiConnectionId"
                                            class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                                        <option value="">{{ __('ui.select') }}</option>
                                        @foreach ($aiConnections as $opt)
                                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif
                    @endif

                    <flux:button variant="primary" type="submit" size="sm">{{ __('ui.create') }}</flux:button>
                </form>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('ui.messenger.chats') }}</flux:heading>
                </div>
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($conversations as $row)
                        <li>
                            <a
                                href="{{ route('messenger.show', $row['id']) }}"
                                @if ($embedMode)
                                    wire:click.prevent="openConversation({{ (int) $row['id'] }})"
                                @else
                                    wire:navigate
                                @endif
                                class="flex flex-col gap-1 px-3 py-2 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/80 {{ (int) $activeConversationId === (int) $row['id'] ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="min-w-0 truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        @if (($row['type'] ?? '') === 'direct' && !empty($row['direct_peer']))
                                            {{ $row['direct_peer']['name'] ?? ('#'.$row['direct_peer']['id']) }}
                                        @else
                                            {{ $row['title'] ?? __('ui.messenger.chat') }}
                                        @endif
                                    </span>
                                    @if (($row['unread_count'] ?? 0) > 0)
                                        <span class="shrink-0 rounded-full bg-blue-500 px-2 py-0.5 text-xs font-medium text-white">
                                            {{ $row['unread_count'] }}
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($row['last_message']))
                                    @php $lm = $row['last_message']; @endphp
                                    <span class="line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        @if (($lm['kind'] ?? '') === 'system')
                                            {{ __('ui.messenger.system') }}:
                                        @elseif (($row['type'] ?? '') === 'ai' && ($lm['user_id'] ?? null) === null)
                                            {{ __('ui.messenger.assistant') }}:
                                        @elseif (!empty($lm['author']['name']))
                                            {{ $lm['author']['name'] }}:
                                        @endif
                                        {{ $lm['body'] ?? '…' }}
                                    </span>
                                @endif
                            </a>
                        </li>
                    @empty
                        <li class="px-3 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.messenger.no_chats') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Окно чата --}}
        <div class="flex min-h-[min(50vh,28rem)] min-w-0 flex-1 flex-col overflow-hidden rounded-lg border border-zinc-200 lg:min-h-0 lg:h-full dark:border-zinc-700">
            @if ($activeConversationId)
                <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                    <livewire:messenger.messenger-room
                        :conversation-id="$activeConversationId"
                        :embedded="true"
                        :key="'messenger-room-'.$activeConversationId"
                    />
                </div>
            @else
                <div class="flex min-h-[12rem] flex-1 items-center justify-center p-8 lg:min-h-0">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('ui.messenger.pick_chat') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
