<x-layouts.second_level_layout :title="__('ui.kanban.logs')" :buttons="[]">
    <div
        class="space-y-4"
        x-data="{
            detailPayload: '',
            openLogDetails(payload) {
                this.detailPayload = payload
                document.dispatchEvent(new CustomEvent('modal-show', { bubbles: true, detail: { name: 'kanban-log-payload' } }))
            },
        }"
    >
        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('ui.kanban.logs_hint') }}
        </flux:text>
        <flux:button :href="route('kanban')" variant="ghost" size="sm" wire:navigate class="mb-2">
            {{ __('ui.kanban.title') }}
        </flux:button>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50/80 dark:border-zinc-700 dark:bg-zinc-800/80">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('ui.when') }}</th>
                        <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('ui.who') }}</th>
                        <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('ui.action') }}</th>
                        <th class="whitespace-nowrap px-3 py-2 font-semibold">{{ __('ui.kanban.board') }}</th>
                        <th class="min-w-[12rem] px-3 py-2 font-semibold">{{ __('ui.details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($logs as $log)
                        <tr class="align-top hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                            <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                {{ $log->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i:s') }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->user?->name ?? '—' }}</div>
                                <div class="text-xs text-zinc-500">{{ $log->user?->email }}</div>
                            </td>
                            <td class="px-3 py-2">
                                {{ \App\Models\KanbanActivityLog::labelForAction($log->action) }}
                            </td>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-300">
                                @if ($log->kanban_board_id !== null)
                                    @if ($log->board)
                                        <span class="line-clamp-2">{{ $log->board->name }}</span>
                                    @else
                                        <span class="text-zinc-500">#{{ $log->kanban_board_id }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if (! empty($log->payload))
                                    @php
                                        $logPayloadJson = json_encode($log->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                                    @endphp
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        type="button"
                                        x-on:click="openLogDetails({!! \Illuminate\Support\Js::from($logPayloadJson) !!})"
                                    >
                                        {{ __('ui.details') }}
                                    </flux:button>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-zinc-500">
                                {{ __('ui.notfound') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $logs->links() }}
            </div>
        @endif

        <flux:modal name="kanban-log-payload" class="w-full max-w-2xl">
            <div class="space-y-3 pe-8">
                <flux:heading size="lg">{{ __('ui.details') }}</flux:heading>
                <pre
                    class="max-h-[min(60vh,24rem)] overflow-auto whitespace-pre-wrap break-words rounded-md border border-zinc-200/80 bg-zinc-100/80 p-3 font-mono text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-400"
                    x-text="detailPayload"
                ></pre>
            </div>
            <div class="mt-4 flex justify-end">
                <flux:modal.close>
                    <flux:button variant="primary" type="button">{{ __('ui.close') }}</flux:button>
                </flux:modal.close>
            </div>
        </flux:modal>
    </div>
</x-layouts.second_level_layout>
