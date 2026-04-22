<div class="w-full max-w-4xl space-y-6" wire:poll.keep-alive.20s="refreshList">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:link href="{{ route('messenger.settings.notifications') }}" wire:navigate class="text-sm">
            {{ __('ui.messenger.notifications_title') }}
        </flux:link>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800/80">
        <flux:heading size="sm" class="mb-3">{{ __('ui.messenger.new_chat') }}</flux:heading>
        <form wire:submit="createChat" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.type') }}</label>
                <select wire:model.live="createType"
                        class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
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
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
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
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
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
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                <option value="">{{ __('ui.select') }}</option>
                                @foreach ($aiConnections as $opt)
                                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endif
            @endif

            <flux:button variant="primary" type="submit" square icon="plus" :title="__('ui.create')" />
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/80">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('ui.messenger.chats') }}</flux:heading>
        </div>
        <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($conversations as $row)
                <li>
                    <a href="{{ route('messenger.show', $row['id']) }}" wire:navigate
                       class="flex flex-col gap-1 px-4 py-3 transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                @if (($row['type'] ?? '') === 'direct' && !empty($row['direct_peer']))
                                    {{ $row['direct_peer']['name'] ?? ('#'.$row['direct_peer']['id']) }}
                                @else
                                    {{ $row['title'] ?? __('ui.messenger.chat') }}
                                @endif
                            </span>
                            @if (($row['unread_count'] ?? 0) > 0)
                                <span class="rounded-full bg-accent px-2 py-0.5 text-xs font-medium text-white">
                                    {{ $row['unread_count'] }}
                                </span>
                            @endif
                        </div>
                        @if (!empty($row['last_message']))
                            @php $lm = $row['last_message']; @endphp
                            <span class="line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
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
                <li class="px-4 py-8 text-center text-sm text-zinc-500">{{ __('ui.messenger.no_chats') }}</li>
            @endforelse
        </ul>
    </div>
</div>
