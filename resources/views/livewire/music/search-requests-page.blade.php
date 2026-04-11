<div class="mx-auto w-full max-w-5xl space-y-6">
    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_goal_label') }}</flux:label>
                <select wire:model="searchGoal" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    @foreach ($searchGoalOptions as $goal)
                        <option value="{{ $goal->value }}">{{ $this->goalLabel($goal) }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_initiator_label') }}</flux:label>
                <select wire:model="initiatorRef" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="">{{ __('ui.select') }}</option>
                    @foreach ($actorOptions as $actor)
                        <option value="{{ $actor['type'] }}:{{ $actor['id'] }}">{{ $actor['label'] }}</option>
                    @endforeach
                </select>
                @error('initiatorRef')
                    <div class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</div>
                @enderror
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_expires_at_label') }}</flux:label>
                <input type="datetime-local" wire:model="expiresAt" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_criteria_label') }}</flux:label>
                <textarea wire:model="criteriaJson" rows="4" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"></textarea>
                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_criteria_hint') }}</div>
                @error('criteriaJson')
                    <div class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</div>
                @enderror
            </flux:field>
        </div>

        <div class="mt-4">
            <flux:button type="button" wire:click="createRequest" variant="primary">
                {{ __('ui.music.search_requests_create_btn') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-3">
            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_filter_status') }}</flux:label>
                <select wire:model.live="statusFilter" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="all">{{ __('ui.music.search_requests_filter_all') }}</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ $this->statusLabel($status) }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_filter_goal') }}</flux:label>
                <select wire:model.live="goalFilter" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="all">{{ __('ui.music.search_requests_filter_all') }}</option>
                    @foreach ($searchGoalOptions as $goal)
                        <option value="{{ $goal->value }}">{{ $this->goalLabel($goal) }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('ui.music.search_requests_filter_initiator') }}</flux:label>
                <select wire:model.live="initiatorFilter" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs focus:border-zinc-500 focus:outline-hidden focus:ring-2 focus:ring-zinc-500/30 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="all">{{ __('ui.music.search_requests_filter_all') }}</option>
                    @foreach ($actorOptions as $actor)
                        <option value="{{ $actor['type'] }}:{{ $actor['id'] }}">{{ $actor['label'] }}</option>
                    @endforeach
                </select>
            </flux:field>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_goal') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_initiator') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_dates') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('ui.music.search_requests_table_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($requests as $request)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                <div>{{ $this->goalLabel($request->search_goal) }}</div>
                                @if (!empty($request->criteria))
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ json_encode($request->criteria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $this->statusLabel($request->status) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $this->initiatorLabel($request) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                <div>{{ __('ui.music.search_requests_submitted_at') }}: {{ optional($request->submitted_at)->format('Y-m-d H:i') }}</div>
                                <div>{{ __('ui.music.search_requests_expires_at_short') }}: {{ optional($request->expires_at)->format('Y-m-d H:i') ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if (in_array($request->status->value, ['open', 'awaiting_approval'], true))
                                    <flux:button size="xs" variant="danger" wire:click="cancelRequest({{ $request->id }})">
                                        {{ __('ui.music.search_requests_cancel_btn') }}
                                    </flux:button>
                                @elseif (in_array($request->status->value, ['cancelled', 'expired'], true))
                                    <flux:button size="xs" wire:click="reopenRequest({{ $request->id }})">
                                        {{ __('ui.music.search_requests_reopen_btn') }}
                                    </flux:button>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('ui.music.search_requests_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
