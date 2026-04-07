<div class="flex w-full max-w-3xl flex-col gap-4" style="min-height: 70vh;"
     wire:key="messenger-room-poll-{{ $pollIntervalSeconds }}-{{ $aiWaitingForReply ? 'w' : 'i' }}"
     wire:poll.keep-alive.{{ $pollIntervalSeconds }}s="loadMessages">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:link href="{{ route('messenger.index') }}" wire:navigate class="text-sm">{{ __('ui.back') }}</flux:link>
        <flux:button size="sm" variant="ghost" wire:click="toggleMute" type="button">
            {{ $muted ? __('ui.messenger.unmute_chat') : __('ui.messenger.mute_chat') }}
        </flux:button>
    </div>

    @if ($hasMoreOlder && $nextBeforeId)
        <div>
            <flux:button size="sm" variant="ghost" wire:click="loadOlderMessages" type="button">
                {{ __('ui.messenger.load_older') }}
            </flux:button>
        </div>
    @endif

    <div class="flex flex-1 flex-col gap-2 overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40"
         style="max-height: 50vh;">
        @forelse ($items as $msg)
            <div class="rounded-lg bg-white px-3 py-2 text-sm shadow-xs dark:bg-zinc-800">
                <div class="mb-1 flex items-center justify-between gap-2 text-xs text-zinc-500">
                    <span class="font-medium text-zinc-700 dark:text-zinc-200">
                        @if (($msg['kind'] ?? '') === 'system')
                            {{ __('ui.messenger.system') }}
                        @elseif ($isAiChat && ($msg['user_id'] ?? null) === null)
                            {{ __('ui.messenger.assistant') }}
                        @else
                            {{ $msg['author']['name'] ?? __('ui.messenger.system') }}
                        @endif
                    </span>
                    <span>{{ \Illuminate\Support\Carbon::parse($msg['created_at'])->timezone(config('app.timezone'))->format('d.m H:i') }}</span>
                </div>
                @if (!empty($msg['is_forward']))
                    <div class="mb-1 text-xs text-zinc-500">{{ __('ui.messenger.forwarded') }}</div>
                @endif
                <div class="whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $msg['body'] }}</div>
                @if (!empty($msg['attachments']))
                    <ul class="mt-2 list-inside list-disc text-xs text-zinc-600 dark:text-zinc-400">
                        @foreach ($msg['attachments'] as $att)
                            <li>
                                @if (!empty($att['download_url']))
                                    <a href="{{ $att['download_url'] }}" class="text-accent underline" target="_blank" rel="noopener noreferrer">{{ $att['original_name'] ?? $att['path'] }}</a>
                                @else
                                    {{ $att['original_name'] ?? $att['path'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-center text-sm text-zinc-500">{{ __('ui.messenger.no_messages') }}</p>
        @endforelse
    </div>

    @if ($isAiChat && $aiWaitingForReply)
        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400" wire:loading.remove wire:target="send">
            {{ __('ui.messenger.ai_thinking') }}
        </p>
    @endif

    @if ($isAiChat && config('ai.enabled'))
        <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/80">
            <flux:heading size="sm">{{ __('ui.messenger.ai_skills') }}</flux:heading>

            @if ($editingSkillId)
                <form wire:submit="saveSkillEdit" class="space-y-3">
                    <flux:input wire:model="skillTitle" type="text" :label="__('ui.messenger.skill_title_field')" />
                    <flux:textarea wire:model="skillInstruction" rows="4" :label="__('ui.messenger.skill_instruction_field')" />
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="primary" type="submit">{{ __('ui.save') }}</flux:button>
                        <flux:button variant="ghost" type="button" wire:click="cancelSkillEdit">{{ __('ui.cancel') }}</flux:button>
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
                                @if (!empty($s['owned']))
                                    <div class="flex flex-wrap gap-1">
                                        <flux:button size="xs" variant="ghost" type="button" wire:click="toggleSkillEnabled({{ (int) $s['id'] }})">
                                            {{ !empty($s['enabled']) ? __('ui.messenger.skill_disable') : __('ui.messenger.skill_enable') }}
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

    <form wire:submit="send" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/80">
        <flux:textarea wire:model="body" rows="3" :placeholder="__('ui.messenger.message_placeholder')" />
        <div class="flex flex-wrap items-end gap-3">
            @if (! $isAiChat)
                <div class="min-w-[200px] flex-1">
                    <input type="file" wire:model="attachment" class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 dark:text-zinc-300 dark:file:bg-zinc-700" />
                    <div wire:loading wire:target="attachment" class="mt-1 text-xs text-zinc-500">{{ __('ui.loading') }}</div>
                </div>
            @endif
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                {{ __('ui.messenger.send') }}
            </flux:button>
        </div>
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
