<div class="flex min-h-0 flex-1 flex-col gap-4 p-4">
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    @if (session('info'))
        <flux:callout variant="warning">{{ session('info') }}</flux:callout>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <flux:radio.group wire:model.live="scopeMode" variant="segmented" size="sm">
                <flux:radio value="personal" :label="__('ui.calendar.personal')" />
                <flux:radio value="shared" :label="__('ui.calendar.shared')" />
                <flux:radio value="kanban" :label="__('ui.calendar.kanban')" />
            </flux:radio.group>
            <flux:radio.group wire:model.live="viewMode" variant="segmented" size="sm">
                <flux:radio value="month" :label="__('ui.month')" />
                <flux:radio value="week" :label="__('ui.week')" />
                <flux:radio value="year" :label="__('ui.year')" />
            </flux:radio.group>
            @if ($scopeMode === 'kanban')
                <flux:button type="button" wire:click="openKanbanCalSettings" icon="cog" size="sm" variant="subtle">
                    {{ __('ui.calendar.kanban_log_settings') }}
                </flux:button>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button.group>
                <flux:button wire:click="previousPeriod" icon="chevron-left" size="sm" :title="__('ui.back')" />
                <flux:button wire:click="goToday" size="sm" variant="subtle">{{ __('ui.today') }}</flux:button>
                <flux:button wire:click="nextPeriod" icon="chevron-right" size="sm" :title="__('ui.more')" />
            </flux:button.group>
            <flux:heading size="lg" class="min-w-[10rem] capitalize">{{ $periodLabel }}</flux:heading>
            @if ($scopeMode !== 'kanban')
                @can('create', \App\Models\CalendarEvent::class)
                    <flux:button wire:click="openCreate" icon="plus" size="sm" variant="primary">{{ __('ui.calendar.event') }}</flux:button>
                @endcan
            @endif
        </div>
    </div>

    @if ($scopeMode !== 'kanban')
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.calendar.filter_kind_label') }}</label>
                <select
                    wire:model.live="eventKind"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    @foreach ($eventKindOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.calendar.filter_owner_label') }}</label>
                <select
                    wire:model.live="ownerEntity"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    @foreach ($ownerOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.calendar.filter_date_from') }}</label>
                <input
                    type="date"
                    wire:model.live="filterDateFrom"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('ui.calendar.filter_date_to') }}</label>
                <input
                    type="date"
                    wire:model.live="filterDateTo"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />
            </div>
        </div>
    @endif

    @if ($viewMode === 'month' && $monthGrid)
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr>
                        @foreach ($monthGrid['weekday_labels'] as $wd)
                            <th class="border-b border-zinc-200 px-1 py-2 text-center text-xs font-medium text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                {{ $wd }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthGrid['weeks'] as $wi => $week)
                        <tr>
                            @foreach ($week as $cell)
                                <td
                                    wire:key="mc-{{ $cell['date'] }}-{{ $wi }}"
                                    class="min-h-[6.5rem] w-[14.28%] border border-zinc-100 p-1 align-top dark:border-zinc-800 {{ $cell['in_month'] ? 'bg-white dark:bg-zinc-900' : 'bg-zinc-50/80 dark:bg-zinc-800/40' }}"
                                >
                                    <div class="mb-1 flex items-center justify-between gap-1">
                                        @if ($scopeMode === 'kanban')
                                            <span
                                                class="flex h-6 min-w-6 items-center justify-center rounded text-xs font-semibold text-zinc-800 dark:text-zinc-100 {{ $cell['date'] === $todayDate ? 'ring-1 ring-emerald-500 ring-offset-1 ring-offset-white dark:ring-offset-zinc-900' : '' }}"
                                            >
                                                {{ $cell['d'] }}
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="openCreate('{{ $cell['date'] }}')"
                                                class="flex h-6 min-w-6 items-center justify-center rounded text-xs font-semibold text-zinc-800 hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800 {{ $cell['date'] === $todayDate ? 'ring-1 ring-violet-500 ring-offset-1 ring-offset-white dark:ring-offset-zinc-900' : '' }}"
                                            >
                                                {{ $cell['d'] }}
                                            </button>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        @foreach ($cell['entries'] as $entry)
                                            @if ($entry->isKanban)
                                                <button
                                                    type="button"
                                                    wire:click="openKanbanCard({{ $entry->kanbanCardId }})"
                                                    wire:key="{{ $entry->sortKey }}-{{ $cell['date'] }}"
                                                    class="w-full truncate rounded bg-emerald-500/15 px-1 py-0.5 text-left text-[11px] font-medium text-emerald-900 hover:bg-emerald-500/25 dark:text-emerald-100"
                                                    title="{{ $entry->tooltip }}"
                                                >
                                                    <span class="text-zinc-500 dark:text-zinc-400">{{ $entry->startsAtUtc->timezone($appTimezone)->format('H:i') }}</span>
                                                    {{ $entry->title }}
                                                </button>
                                            @else
                                                @php
                                                    $evColor = $entry->colorHex ?? \App\Models\CalendarEvent::DEFAULT_EVENT_COLOR;
                                                @endphp
                                                @if ($entry->canEditCalendarEvent)
                                                    <button
                                                        type="button"
                                                        wire:click="openEdit({{ $entry->calendarEventId }})"
                                                        wire:key="{{ $entry->sortKey }}-{{ $cell['date'] }}"
                                                        class="w-full truncate rounded px-1 py-0.5 text-left text-[11px] font-medium text-zinc-800 hover:opacity-90 dark:text-zinc-200"
                                                        style="background-color: color-mix(in srgb, {{ $evColor }} 18%, transparent);"
                                                        title="{{ $entry->tooltip }}"
                                                    >
                                                        @if (! $entry->allDay)
                                                            <span class="text-zinc-500 dark:text-zinc-400">{{ $entry->startsAtUtc->timezone($appTimezone)->format('H:i') }}</span>
                                                        @endif
                                                        {{ $entry->title }}
                                                    </button>
                                                @else
                                                    <div
                                                        class="truncate rounded px-1 py-0.5 text-[11px] text-zinc-600 dark:text-zinc-400"
                                                        style="background-color: color-mix(in srgb, {{ $evColor }} 10%, transparent);"
                                                        title="{{ $entry->tooltip }}"
                                                    >
                                                        {{ $entry->title }}
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($viewMode === 'week' && $weekDays)
        <div class="grid gap-3 md:grid-cols-7">
            @foreach ($weekDays as $d)
                <div
                    wire:key="wc-{{ $d['date'] }}"
                    class="flex min-h-[12rem] flex-col rounded-xl border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900 {{ $d['is_today'] ? ($scopeMode === 'kanban' ? 'ring-2 ring-emerald-500/50' : 'ring-2 ring-violet-500/50') : '' }}"
                >
                    <div class="mb-2 flex items-center justify-between border-b border-zinc-100 pb-2 dark:border-zinc-800">
                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ $d['label'] }}</span>
                        @if ($scopeMode !== 'kanban')
                            @can('create', \App\Models\CalendarEvent::class)
                                <flux:button size="xs" variant="ghost" wire:click="openCreate('{{ $d['date'] }}')" icon="plus" />
                            @endcan
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col gap-1 overflow-y-auto">
                        @foreach ($d['entries'] as $entry)
                            @if ($entry->isKanban)
                                <button
                                    type="button"
                                    wire:click="openKanbanCard({{ $entry->kanbanCardId }})"
                                    wire:key="{{ $entry->sortKey }}-{{ $d['date'] }}"
                                    class="rounded-lg border border-emerald-100 bg-emerald-50/80 px-2 py-1.5 text-left text-xs dark:border-emerald-900/40 dark:bg-emerald-950/40"
                                    title="{{ $entry->tooltip }}"
                                >
                                    <span class="font-medium leading-tight text-zinc-900 dark:text-zinc-100">{{ $entry->title }}</span>
                                    <span class="mt-0.5 block text-[10px] text-zinc-500 dark:text-zinc-400">
                                        {{ $entry->startsAtUtc->timezone($appTimezone)->format('H:i') }}
                                    </span>
                                </button>
                            @else
                                @php
                                    $evColor = $entry->colorHex ?? \App\Models\CalendarEvent::DEFAULT_EVENT_COLOR;
                                @endphp
                                @if ($entry->canEditCalendarEvent)
                                    <button
                                        type="button"
                                        wire:click="openEdit({{ $entry->calendarEventId }})"
                                        wire:key="{{ $entry->sortKey }}-{{ $d['date'] }}"
                                        class="rounded-lg border px-2 py-1.5 text-left text-xs hover:opacity-90"
                                        style="border-color: color-mix(in srgb, {{ $evColor }} 40%, transparent); background-color: color-mix(in srgb, {{ $evColor }} 12%, transparent);"
                                        title="{{ $entry->tooltip }}"
                                    >
                                        <span class="font-medium leading-tight text-zinc-900 dark:text-zinc-100">{{ $entry->title }}</span>
                                        @if (! $entry->allDay)
                                            <span class="mt-0.5 block text-[10px] text-zinc-500 dark:text-zinc-400">
                                                {{ $entry->startsAtUtc->timezone($appTimezone)->format('H:i') }}
                                                — {{ $entry->endsAtUtc->timezone($appTimezone)->format('H:i') }}
                                            </span>
                                        @else
                                            <span class="mt-0.5 block text-[10px] text-zinc-500 dark:text-zinc-400">{{ __('ui.calendar.all_day') }}</span>
                                        @endif
                                    </button>
                                @else
                                    <div
                                        class="rounded-lg px-2 py-1.5 text-xs text-zinc-700 dark:text-zinc-300"
                                        style="background-color: color-mix(in srgb, {{ $evColor }} 10%, transparent);"
                                        title="{{ $entry->tooltip }}"
                                    >
                                        {{ $entry->title }}
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($viewMode === 'year' && $yearModel)
        @php
            $yearBtnClass =
                $scopeMode === 'kanban'
                    ? 'flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/25 text-[11px] font-semibold text-emerald-900 hover:bg-emerald-500/40 dark:text-emerald-100'
                    : 'flex h-7 w-7 items-center justify-center rounded-full bg-amber-500/25 text-[11px] font-semibold text-amber-900 hover:bg-amber-500/40 dark:text-amber-100';
        @endphp
        <div class="overflow-y-auto rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="md" class="mb-4">{{ $yearModel['year'] }}</flux:heading>
            <div class="flex flex-col gap-8">
                @foreach ($yearModel['months'] as $mi => $monthBlock)
                    <div wire:key="ym-{{ $yearModel['year'] }}-{{ $mi }}">
                        <div class="mb-2 text-sm font-semibold capitalize text-zinc-800 dark:text-zinc-200">
                            {{ $monthBlock['title'] }}
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] text-zinc-500 dark:text-zinc-400">
                            @foreach ($yearModel['weekday_labels'] as $wd)
                                <div class="pb-1 font-medium">{{ $wd }}</div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            @foreach ($monthBlock['cells'] as $ci => $cell)
                                @if (! empty($cell['empty']))
                                    <div wire:key="yc-{{ $mi }}-{{ $ci }}" class="aspect-square max-h-8"></div>
                                @else
                                    <div
                                        wire:key="yc-{{ $mi }}-{{ $ci }}-{{ $cell['d'] }}"
                                        class="flex aspect-square max-h-8 items-center justify-center"
                                    >
                                        @if ($cell['has_events'])
                                            <button
                                                type="button"
                                                title="{{ $cell['tooltip'] }}"
                                                wire:click="openYearDayModal('{{ $cell['date'] }}')"
                                                class="{{ $yearBtnClass }}"
                                            >
                                                {{ $cell['d'] }}
                                            </button>
                                        @else
                                            <span class="text-[11px] text-zinc-600 dark:text-zinc-400">{{ $cell['d'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <flux:text class="mt-6 text-xs text-zinc-500 dark:text-zinc-400">
                @if ($scopeMode === 'kanban')
                    {{ __('ui.calendar.year_hint_kanban') }}
                @else
                    {{ __('ui.calendar.year_hint_calendar') }}
                @endif
            </flux:text>
        </div>
    @endif

    <flux:modal wire:model="showYearDayModal" name="calendar-year-day" class="max-h-[90vh] max-w-md overflow-y-auto">
        <flux:heading>{{ $yearDayModalLabel }}</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            @if ($scopeMode === 'kanban')
                {{ __('ui.calendar.year_day_kanban') }}
            @else
                {{ __('ui.calendar.year_day_events') }}
            @endif
        </flux:text>
        <ul class="mt-4 flex max-h-[60vh] flex-col gap-2 overflow-y-auto p-0 list-none">
            @foreach ($yearDayModalItems as $yi => $item)
                @if ($item['is_kanban'] && $item['kanban_card_id'])
                    <li wire:key="yd-k-{{ $item['kanban_card_id'] }}-{{ $yi }}">
                        <button
                            type="button"
                            wire:click="yearDayModalOpenKanban({{ $item['kanban_card_id'] }})"
                            class="flex w-full flex-col rounded-lg border border-emerald-100 bg-emerald-50/80 px-3 py-2 text-left text-sm transition hover:bg-emerald-100/80 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/30"
                        >
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['time_label'] }}</span>
                        </button>
                    </li>
                @elseif (! $item['is_kanban'] && $item['calendar_event_id'] && $item['can_edit'])
                    @php
                        $rowColor = $item['color_hex'] ?? \App\Models\CalendarEvent::DEFAULT_EVENT_COLOR;
                    @endphp
                    <li wire:key="yd-e-{{ $item['calendar_event_id'] }}-{{ $yi }}">
                        <button
                            type="button"
                            wire:click="yearDayModalOpenCalendarEvent({{ $item['calendar_event_id'] }})"
                            class="flex w-full flex-col rounded-lg border px-3 py-2 text-left text-sm transition hover:opacity-90"
                            style="border-color: color-mix(in srgb, {{ $rowColor }} 40%, transparent); background-color: color-mix(in srgb, {{ $rowColor }} 12%, transparent);"
                        >
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['time_label'] }}</span>
                        </button>
                    </li>
                @elseif (! $item['is_kanban'] && $item['calendar_event_id'])
                    @php
                        $rowColorRo = $item['color_hex'] ?? \App\Models\CalendarEvent::DEFAULT_EVENT_COLOR;
                    @endphp
                    <li wire:key="yd-er-{{ $item['calendar_event_id'] }}-{{ $yi }}">
                        <div
                            class="flex w-full flex-col rounded-lg px-3 py-2 text-left text-sm text-zinc-700 dark:text-zinc-300"
                            style="background-color: color-mix(in srgb, {{ $rowColorRo }} 10%, transparent);"
                        >
                            <span class="font-medium">{{ $item['title'] }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['time_label'] }}</span>
                        </div>
                    </li>
                @endif
            @endforeach
        </ul>
        <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            @if ($scopeMode !== 'kanban')
                @can('create', \App\Models\CalendarEvent::class)
                    <flux:button type="button" variant="primary" size="sm" wire:click="yearDayModalCreateEvent" icon="plus">
                        {{ __('ui.calendar.new_event') }}
                    </flux:button>
                @endcan
            @endif
            <flux:button type="button" variant="ghost" size="sm" wire:click="closeYearDayModal">{{ __('ui.close') }}</flux:button>
        </div>
    </flux:modal>

    <flux:modal wire:model="showKanbanCalSettings" name="kanban-cal-settings" class="max-h-[90vh] max-w-lg overflow-y-auto">
        <flux:heading>{{ __('ui.calendar.kanban_log_modal_title') }}</flux:heading>
        <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('ui.calendar.kanban_log_modal_text') }}
        </flux:text>
        <div class="mt-4 flex flex-col gap-3">
            <flux:checkbox wire:model="kcalShowCreated" :label="__('ui.calendar.kanban_log_created')" />
            <flux:checkbox wire:model="kcalShowDue" :label="__('ui.calendar.kanban_log_due')" />
            <flux:checkbox wire:model="kcalShowMoves" :label="__('ui.calendar.kanban_log_moves')" />
            <flux:checkbox wire:model="kcalMovesAllTargets" :label="__('ui.calendar.kanban_log_moves_all')" />
            <div class="space-y-2 {{ $kcalMovesAllTargets ? 'pointer-events-none opacity-45' : '' }}">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('ui.calendar.kanban_log_moves_targets') }}
                </flux:text>
                <div class="max-h-52 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                    @forelse ($kanbanSettingColumnOptions as $opt)
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                            <input type="checkbox" wire:model.live="kcalMoveTargetIds" value="{{ $opt['id'] }}" class="rounded border-zinc-300 dark:border-zinc-600" />
                            {{ $opt['label'] }}
                        </label>
                    @empty
                        <flux:text class="text-xs text-zinc-500">{{ __('ui.calendar.kanban_log_no_columns') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
            <flux:button type="button" variant="ghost" wire:click="closeKanbanCalSettings">{{ __('ui.cancel') }}</flux:button>
            <flux:button type="button" variant="primary" square wire:click="saveKanbanCalSettings" :title="__('ui.save')" icon="save-floppy" />
        </div>
    </flux:modal>

    <flux:modal wire:model="showModal" name="calendar-event" class="max-h-[90vh] max-w-lg overflow-y-auto">
        <flux:heading>{{ $editingId ? __('ui.calendar.edit_event') : __('ui.calendar.new_event_title') }}</flux:heading>
        <form wire:submit="saveEvent" class="mt-4 flex flex-col gap-4">
            <flux:input wire:model="formTitle" :label="__('ui.name')" required />
            <flux:textarea wire:model="formDescription" :label="__('ui.description')" rows="3" />

            <flux:checkbox wire:model.live="formAllDay" :label="__('ui.calendar.all_day')" />

            @if ($scopeMode === 'personal' || $editingId)
                <flux:checkbox wire:model="formIsPublic" :label="__('ui.calendar.is_public_label')" />
            @elseif (! $editingId)
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('ui.calendar.is_public_hint_create_shared') }}</flux:text>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input type="date" wire:model="formStartDate" :label="__('ui.start_date')" />
                <flux:input type="date" wire:model="formEndDate" :label="__('ui.end_date')" />
            </div>
            @if (! $formAllDay)
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:input type="time" wire:model="formStartTime" :label="__('ui.start_time')" />
                    <flux:input type="time" wire:model="formEndTime" :label="__('ui.end_time')" />
                </div>
            @endif

            <div>
                <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.calendar.color_label') }}</span>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    @foreach ($eventColorPresets as $preset)
                        <button
                            type="button"
                            wire:click="$set('formColor', '{{ $preset }}')"
                            wire:key="preset-{{ $preset }}"
                            class="h-8 w-8 rounded-full border-2 shadow-sm transition {{ strcasecmp($formColor, $preset) === 0 ? 'border-zinc-900 ring-2 ring-zinc-400 dark:border-white dark:ring-zinc-500' : 'border-zinc-200 dark:border-zinc-600' }}"
                            style="background-color: {{ $preset }}"
                            title="{{ $preset }}"
                        ></button>
                    @endforeach
                </div>
                <input
                    type="color"
                    wire:model.live="formColor"
                    class="h-10 w-full cursor-pointer rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900"
                />
                @error('formColor')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('ui.calendar.reminder_label') }}</label>
                <select
                    wire:model="formReminderMinutes"
                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                >
                    <option value="">{{ __('ui.calendar.reminder_none') }}</option>
                    @foreach ($reminderOptions as $m)
                        <option value="{{ $m }}">{{ __('ui.calendar.reminder_min', ['min' => $m]) }}</option>
                    @endforeach
                </select>
                @error('formReminderMinutes')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($formCanDelete && $editingId)
                    <flux:button
                        type="button"
                        variant="danger"
                        wire:click="deleteEvent({{ $editingId }})"
                        wire:confirm="{{ __('ui.calendar.delete_confirm') }}"
                    >
                        {{ __('ui.delete') }}
                    </flux:button>
                @endif
                <flux:button type="button" variant="ghost" wire:click="closeModal">{{ __('ui.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" square :title="__('ui.save')" icon="save-floppy" />
            </div>
        </form>
    </flux:modal>
</div>
