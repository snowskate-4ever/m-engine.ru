<div>
    @if ($open)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 pt-[max(1rem,8vh)]" wire:click="close" wire:keydown.escape.window="close">
            <div class="relative w-full max-w-md rounded-xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-600 dark:bg-zinc-900" @click.stop>
                <div class="flex items-center justify-between gap-2 border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('ui.messenger.new_chat') }}</flux:heading>
                    <flux:button type="button" size="xs" variant="ghost" class="h-7 w-7 shrink-0 p-0" wire:click="close" :title="__('ui.close')">
                        <flux:icon.x-mark class="size-4" />
                    </flux:button>
                </div>

                <form wire:submit="createChat" class="max-h-[min(70vh,32rem)] space-y-3 overflow-y-auto p-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.type') }}</label>
                        <select
                            wire:model.live="createType"
                            class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-900 outline-none ring-offset-2 focus:ring-2 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="direct">{{ __('ui.messenger.direct') }}</option>
                            <option value="group">{{ __('ui.messenger.group') }}</option>
                            @if (config('ai.enabled'))
                                <option value="ai">{{ __('ui.messenger.ai_chat') }}</option>
                            @endif
                        </select>
                    </div>

                    @if ($createType === 'direct')
                        <div>
                            <flux:input
                                wire:model.live.debounce.250ms="peerSearch"
                                type="search"
                                autocomplete="off"
                                :label="__('ui.messenger.peer_search_label')"
                                :placeholder="__('ui.messenger.peer_search_placeholder')"
                            />
                            @error('directUserId')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @if ($selectedPeerLabel !== '')
                                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">{{ __('ui.messenger.peer_selected') }}: {{ $selectedPeerLabel }}</p>
                            @endif
                            @if (count($peerResults) > 0)
                                <ul class="mt-2 max-h-48 overflow-y-auto rounded-md border border-zinc-200 dark:border-zinc-700">
                                    @foreach ($peerResults as $row)
                                        <li class="border-b border-zinc-100 last:border-b-0 dark:border-zinc-800">
                                            <button
                                                type="button"
                                                wire:click="selectPeer({{ (int) $row['id'] }})"
                                                @class([
                                                    'flex w-full flex-col gap-0.5 px-3 py-2 text-left text-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-800/80',
                                                    'bg-zinc-100 dark:bg-zinc-800' => (string) $directUserId === (string) $row['id'],
                                                ])
                                            >
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row['name'] }}</span>
                                                <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $row['email'] }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @elseif (mb_strlen(trim($peerSearch)) >= 1)
                                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('ui.messenger.peer_no_results') }}</p>
                            @endif
                        </div>
                    @elseif ($createType === 'group')
                        <flux:input wire:model="groupTitle" type="text" :label="__('ui.messenger.group_title')" />
                        <flux:textarea wire:model="groupUserIds" rows="2" :label="__('ui.messenger.member_ids')" />
                    @elseif ($createType === 'ai')
                        <flux:input wire:model="aiTitle" type="text" :label="__('ui.messenger.ai_chat_title')" />
                        <div>
                            <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.messenger.ai_source') }}</label>
                            <select
                                wire:model.live="aiSource"
                                class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm text-zinc-900 outline-none ring-offset-2 focus:ring-2 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            >
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
                                    <select
                                        wire:model="aiServerModelId"
                                        class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                    >
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
                                    <select
                                        wire:model="aiConnectionId"
                                        class="w-full rounded-md border border-zinc-200 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                    >
                                        <option value="">{{ __('ui.select') }}</option>
                                        @foreach ($aiConnections as $opt)
                                            <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif
                    @endif

                    <div class="flex justify-end pt-1">
                        <flux:button variant="primary" type="submit" size="sm" class="h-9 w-9 shrink-0 p-0" icon="plus" :title="__('ui.messenger.create_chat')" />
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
