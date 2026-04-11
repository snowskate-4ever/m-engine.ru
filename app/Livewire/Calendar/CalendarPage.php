<?php

declare(strict_types=1);

namespace App\Livewire\Calendar;

use App\Models\CalendarEvent;
use App\Models\KanbanColumn;
use App\Models\UserKanbanCalendarSetting;
use App\Services\Kanban\KanbanCalendarEventService;
use App\Support\Calendar\CalendarGridEntry;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CalendarPage extends Component
{
    public string $scopeMode = 'personal';

    public string $viewMode = 'month';

    /** @var string Y-m-d anchor in app timezone */
    public string $cursorDate = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $formTitle = '';

    public string $formDescription = '';

    public bool $formAllDay = false;

    public bool $formIsPublic = false;

    public string $formStartDate = '';

    public string $formEndDate = '';

    public string $formStartTime = '09:00';

    public string $formEndTime = '10:00';

    public ?int $formReminderMinutes = null;

    /** HEX #RRGGBB */
    public string $formColor = '';

    public bool $formCanDelete = false;

    public bool $showKanbanCalSettings = false;

    public bool $kcalShowCreated = true;

    public bool $kcalShowDue = true;

    public bool $kcalShowMoves = true;

    public bool $kcalMovesAllTargets = true;

    /** @var list<int|string> */
    public array $kcalMoveTargetIds = [];

    /**
     * @var list<array{id:int,label:string}>
     */
    public array $kanbanSettingColumnOptions = [];

    public bool $showYearDayModal = false;

    /** Y-m-d */
    public string $yearDayModalDate = '';

    public string $yearDayModalLabel = '';

    /**
     * @var list<array{title:string,time_label:string,is_kanban:bool,kanban_card_id:?int,calendar_event_id:?int,can_edit:bool,color_hex:?string}>
     */
    public array $yearDayModalItems = [];

    private function appTz(): string
    {
        return (string) config('app.timezone');
    }

    public function mount(): void
    {
        $this->cursorDate = CarbonImmutable::now($this->appTz())->toDateString();
        $this->formColor = CalendarEvent::DEFAULT_EVENT_COLOR;
        $this->authorize('viewAny', CalendarEvent::class);
    }

    public function previousPeriod(): void
    {
        $c = $this->cursorLocal();
        $this->cursorDate = match ($this->viewMode) {
            'month' => $c->subMonth()->toDateString(),
            'week' => $c->subWeek()->toDateString(),
            'year' => $c->subYear()->toDateString(),
            default => $c->toDateString(),
        };
    }

    public function nextPeriod(): void
    {
        $c = $this->cursorLocal();
        $this->cursorDate = match ($this->viewMode) {
            'month' => $c->addMonth()->toDateString(),
            'week' => $c->addWeek()->toDateString(),
            'year' => $c->addYear()->toDateString(),
            default => $c->toDateString(),
        };
    }

    public function goToday(): void
    {
        $this->cursorDate = CarbonImmutable::now($this->appTz())->toDateString();
    }

    public function openCreate(?string $date = null): void
    {
        if ($this->scopeMode === 'kanban') {
            return;
        }

        $this->authorize('create', CalendarEvent::class);
        $this->editingId = null;
        $day = $date !== null ? CarbonImmutable::parse($date, $this->appTz()) : $this->cursorLocal();
        $this->formTitle = '';
        $this->formDescription = '';
        $this->formAllDay = false;
        $this->formIsPublic = $this->scopeMode === 'shared';
        $this->formStartDate = $day->toDateString();
        $this->formEndDate = $day->toDateString();
        $this->formStartTime = '09:00';
        $this->formEndTime = '10:00';
        $this->formReminderMinutes = null;
        $this->formColor = CalendarEvent::DEFAULT_EVENT_COLOR;
        $this->formCanDelete = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        if ($this->scopeMode === 'kanban') {
            return;
        }

        $event = CalendarEvent::query()->findOrFail($id);
        $this->authorize('update', $event);
        $this->editingId = $event->id;
        $this->formTitle = $event->title;
        $this->formDescription = (string) ($event->description ?? '');
        $this->formAllDay = $event->all_day;
        $this->formIsPublic = $event->is_public;
        $tz = $this->appTz();
        $startM = $event->starts_at->timezone($tz);
        $endM = $event->ends_at->timezone($tz);
        if ($event->all_day) {
            $this->formStartDate = $startM->toDateString();
            $this->formEndDate = $endM->toDateString();
        } else {
            $this->formStartDate = $startM->toDateString();
            $this->formEndDate = $endM->toDateString();
            $this->formStartTime = $startM->format('H:i');
            $this->formEndTime = $endM->format('H:i');
        }
        $this->formReminderMinutes = $event->reminder_minutes;
        $rawColor = (string) ($event->color ?? '');
        $this->formColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $rawColor)
            ? '#'.strtoupper(substr($rawColor, 1))
            : CalendarEvent::DEFAULT_EVENT_COLOR;
        $this->formCanDelete = Gate::check('delete', $event);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->formCanDelete = false;
    }

    public function saveEvent(): void
    {
        if ($this->scopeMode === 'kanban') {
            return;
        }

        $user = auth()->user();
        if ($user === null) {
            return;
        }

        if ($this->formReminderMinutes === '' || $this->formReminderMinutes === 0) {
            $this->formReminderMinutes = null;
        }

        $this->validate([
            'formTitle' => ['required', 'string', 'max:255'],
            'formDescription' => ['nullable', 'string', 'max:20000'],
            'formAllDay' => ['boolean'],
            'formIsPublic' => ['boolean'],
            'formStartDate' => ['required', 'date'],
            'formEndDate' => ['required', 'date', 'after_or_equal:formStartDate'],
            'formStartTime' => ['required_if:formAllDay,false', 'nullable', 'date_format:H:i'],
            'formEndTime' => ['required_if:formAllDay,false', 'nullable', 'date_format:H:i'],
            'formReminderMinutes' => ['nullable', 'in:10,15,30,45,60'],
            'formColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'formTitle.required' => __('ui.calendar.validation_title'),
            'formEndDate.after_or_equal' => __('ui.calendar.validation_end_after_start'),
            'formColor.regex' => __('ui.calendar.validation_color'),
        ]);

        $tz = $this->appTz();
        $colorHex = '#'.strtoupper(substr(trim($this->formColor), 1));

        if (! $this->formAllDay) {
            $startAt = CarbonImmutable::parse($this->formStartDate.' '.$this->formStartTime, $tz);
            $endAt = CarbonImmutable::parse($this->formEndDate.' '.$this->formEndTime, $tz);
            if ($endAt->lessThanOrEqualTo($startAt)) {
                $this->addError('formEndTime', __('ui.calendar.validation_end_after_start_time'));

                return;
            }
        }

        $isPublic = $this->scopeMode === 'shared' ? true : $this->formIsPublic;

        if ($this->formAllDay) {
            $startsAt = CarbonImmutable::parse($this->formStartDate, $tz)->startOfDay()->utc();
            $endsAt = CarbonImmutable::parse($this->formEndDate, $tz)->endOfDay()->utc();
        } else {
            $startsAt = CarbonImmutable::parse($this->formStartDate.' '.$this->formStartTime, $tz)->utc();
            $endsAt = CarbonImmutable::parse($this->formEndDate.' '.$this->formEndTime, $tz)->utc();
        }

        $wasEdit = $this->editingId !== null;

        if ($this->editingId === null) {
            $this->authorize('create', CalendarEvent::class);
            CalendarEvent::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
                'is_public' => $isPublic,
                'title' => $this->formTitle,
                'description' => $this->formDescription !== '' ? $this->formDescription : null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'all_day' => $this->formAllDay,
                'reminder_minutes' => $this->formReminderMinutes,
                'color' => $colorHex,
            ]);
        } else {
            $event = CalendarEvent::query()->findOrFail($this->editingId);
            $this->authorize('update', $event);
            $event->update([
                'is_public' => $isPublic,
                'title' => $this->formTitle,
                'description' => $this->formDescription !== '' ? $this->formDescription : null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'all_day' => $this->formAllDay,
                'reminder_minutes' => $this->formReminderMinutes,
                'color' => $colorHex,
            ]);
        }

        $this->closeModal();
        session()->flash('success', $wasEdit ? __('ui.calendar.saved') : __('ui.calendar.created'));
    }

    public function deleteEvent(int $id): void
    {
        $event = CalendarEvent::query()->findOrFail($id);
        $this->authorize('delete', $event);
        $event->delete();
        if ($this->editingId === $id) {
            $this->closeModal();
        }
        session()->flash('success', __('ui.calendar.deleted'));
    }

    public function openKanbanCard(int $cardId): void
    {
        $this->redirect(route('kanban', ['open_card' => $cardId]), navigate: true);
    }

    public function openKanbanCalSettings(): void
    {
        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $s = UserKanbanCalendarSetting::forUser($user);
        $this->kcalShowCreated = $s->show_card_created_events;
        $this->kcalShowDue = $s->show_due_events;
        $this->kcalShowMoves = $s->show_column_move_events;
        $this->kcalMovesAllTargets = $s->column_moves_include_all_targets;
        $this->kcalMoveTargetIds = array_values(array_map(static fn ($v) => (int) $v, $s->column_move_target_ids ?? []));

        $this->kanbanSettingColumnOptions = KanbanColumn::query()
            ->whereHas('board', static fn ($q) => $q->forUserAccess($user))
            ->with('board:id,name')
            ->orderBy('kanban_board_id')
            ->orderBy('position')
            ->get()
            ->map(static fn (KanbanColumn $col) => [
                'id' => $col->id,
                'label' => ($col->board !== null && $col->board->name !== '' ? $col->board->name : __('ui.kanban.board')).' — '.$col->name,
            ])
            ->all();

        $this->showKanbanCalSettings = true;
    }

    public function saveKanbanCalSettings(): void
    {
        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $ids = collect($this->kcalMoveTargetIds)->map(static fn ($v) => (int) $v)->filter(static fn ($v) => $v > 0)->unique()->values()->all();

        UserKanbanCalendarSetting::forUser($user)->update([
            'show_card_created_events' => $this->kcalShowCreated,
            'show_due_events' => $this->kcalShowDue,
            'show_column_move_events' => $this->kcalShowMoves,
            'column_moves_include_all_targets' => $this->kcalMovesAllTargets,
            'column_move_target_ids' => $ids,
        ]);

        $this->showKanbanCalSettings = false;
        session()->flash('success', __('ui.calendar.kanban_settings_saved'));
    }

    public function closeKanbanCalSettings(): void
    {
        $this->showKanbanCalSettings = false;
    }

    public function openYearDayModal(string $date): void
    {
        $items = $this->yearDayItemsForDate($date);
        if ($items === []) {
            return;
        }

        $local = CarbonImmutable::parse($date, $this->appTz());
        $this->yearDayModalDate = $date;
        $locale = app()->getLocale();
        $this->yearDayModalLabel = $local->locale($locale)->translatedFormat('j F Y');
        $this->yearDayModalItems = $items;
        $this->showYearDayModal = true;
    }

    public function closeYearDayModal(): void
    {
        $this->showYearDayModal = false;
    }

    public function updatedShowYearDayModal(bool $value): void
    {
        if (! $value) {
            $this->yearDayModalDate = '';
            $this->yearDayModalLabel = '';
            $this->yearDayModalItems = [];
        }
    }

    public function yearDayModalOpenKanban(int $cardId): void
    {
        $this->closeYearDayModal();
        $this->openKanbanCard($cardId);
    }

    public function yearDayModalOpenCalendarEvent(int $eventId): void
    {
        $this->closeYearDayModal();
        $this->openEdit($eventId);
    }

    public function yearDayModalCreateEvent(): void
    {
        $date = $this->yearDayModalDate;
        $this->closeYearDayModal();
        if ($date !== '') {
            $this->openCreate($date);
        }
    }

    /**
     * @return list<array{title:string,time_label:string,is_kanban:bool,kanban_card_id:?int,calendar_event_id:?int,can_edit:bool,color_hex:?string}>
     */
    private function yearDayItemsForDate(string $date): array
    {
        $local = CarbonImmutable::parse($date, $this->appTz());
        $dayStartUtc = $local->startOfDay()->utc();
        $dayEndUtc = $local->endOfDay()->utc();
        $list = $this->gridEntriesInUtcRange($dayStartUtc, $dayEndUtc);

        return $list->map(function (CalendarGridEntry $e): array {
            $tz = $this->appTz();
            if ($e->isKanban) {
                $timeLabel = $e->startsAtUtc->timezone($tz)->format('H:i');
            } elseif ($e->allDay) {
                $timeLabel = __('ui.calendar.all_day');
            } else {
                $timeLabel = $e->startsAtUtc->timezone($tz)->format('H:i')
                    .' — '.$e->endsAtUtc->timezone($tz)->format('H:i');
            }

            return [
                'title' => $e->title,
                'time_label' => $timeLabel,
                'is_kanban' => $e->isKanban,
                'kanban_card_id' => $e->kanbanCardId,
                'calendar_event_id' => $e->calendarEventId,
                'can_edit' => $e->canEditCalendarEvent,
                'color_hex' => $e->colorHex,
            ];
        })->values()->all();
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        return view('livewire.calendar.calendar-page', [
            'periodLabel' => $this->periodLabel($locale),
            'monthGrid' => $this->viewMode === 'month' ? $this->buildMonthGrid($locale) : null,
            'weekDays' => $this->viewMode === 'week' ? $this->buildWeekDays($locale) : null,
            'yearModel' => $this->viewMode === 'year' ? $this->buildYearModel($locale) : null,
            'reminderOptions' => CalendarEvent::REMINDER_OPTIONS,
            'eventColorPresets' => CalendarEvent::COLOR_PRESETS,
            'todayDate' => CarbonImmutable::now($this->appTz())->toDateString(),
            'appTimezone' => $this->appTz(),
        ]);
    }

    private function cursorLocal(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->cursorDate, $this->appTz());
    }

    private function periodLabel(string $locale): string
    {
        $c = $this->cursorLocal()->locale($locale);

        return match ($this->viewMode) {
            'month' => $c->translatedFormat('F Y'),
            'week' => (function () use ($c, $locale) {
                $ws = $c->locale($locale)->startOfWeek(CarbonInterface::MONDAY);
                $we = $c->locale($locale)->endOfWeek(CarbonInterface::SUNDAY);

                return $ws->day === $we->day && $ws->month === $we->month
                    ? $ws->locale($locale)->translatedFormat('j F Y')
                    : $ws->locale($locale)->translatedFormat('j M').' — '.$we->locale($locale)->translatedFormat('j F Y');
            })(),
            'year' => (string) $c->year,
            default => $c->toDateString(),
        };
    }

    /**
     * @return Collection<int, CalendarGridEntry>
     */
    private function gridEntriesInUtcRange(CarbonImmutable $startUtc, CarbonImmutable $endUtc): Collection
    {
        $user = auth()->user();
        if ($user === null) {
            return collect();
        }

        if ($this->scopeMode === 'kanban') {
            return app(KanbanCalendarEventService::class)->collect($user, $startUtc, $endUtc);
        }

        return $this->baseQuery()
            ->where('starts_at', '<=', $endUtc)
            ->where('ends_at', '>=', $startUtc)
            ->orderBy('starts_at')
            ->get()
            ->map(static function (CalendarEvent $e) use ($user): CalendarGridEntry {
                $canEdit = (int) $e->user_id === (int) $user->id;

                return CalendarGridEntry::fromCalendarEvent($e, $canEdit);
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<CalendarEvent>
     */
    private function baseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();
        $q = CalendarEvent::query();
        if ($this->scopeMode === 'personal') {
            $q->where('user_id', $user?->id);
        } elseif ($this->scopeMode === 'shared') {
            $q->where('is_public', true);
        }

        return $q;
    }

    /**
     * @param  Collection<int, CalendarGridEntry>  $all
     * @return Collection<int, CalendarGridEntry>
     */
    private function entriesForUtcDay(Collection $all, CarbonImmutable $localDay): Collection
    {
        $tz = $this->appTz();
        $dayStartUtc = $localDay->timezone($tz)->startOfDay()->utc();
        $dayEndUtc = $localDay->timezone($tz)->endOfDay()->utc();

        return $all->filter(static function (CalendarGridEntry $e) use ($dayStartUtc, $dayEndUtc): bool {
            return $e->startsAtUtc <= $dayEndUtc && $e->endsAtUtc >= $dayStartUtc;
        })->values();
    }

    /**
     * @return array{weeks: array<int, array<int, array<string, mixed>>>, weekday_labels: list<string>}
     */
    private function buildMonthGrid(string $locale): array
    {
        $tz = $this->appTz();
        $c = $this->cursorLocal();
        $monthStart = $c->startOfMonth();
        $monthEnd = $c->endOfMonth();
        $gridStart = $monthStart->locale($locale)->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $monthEnd->locale($locale)->endOfWeek(CarbonInterface::SUNDAY);
        $startUtc = $gridStart->timezone($tz)->startOfDay()->utc();
        $endUtc = $gridEnd->timezone($tz)->endOfDay()->utc();
        $entries = $this->gridEntriesInUtcRange($startUtc, $endUtc);

        $weeks = [];
        $day = $gridStart;
        while ($day->lessThanOrEqualTo($gridEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $local = $day->timezone($tz);
                $week[] = [
                    'date' => $local->toDateString(),
                    'in_month' => $local->month === $c->month,
                    'd' => $local->day,
                    'day_short' => $local->locale($locale)->minDayName,
                    'entries' => $this->entriesForUtcDay($entries, $local),
                ];
                $day = $day->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'weeks' => $weeks,
            'weekday_labels' => self::weekdayLabels($locale, $tz),
        ];
    }

    /**
     * @return list<string>
     */
    private static function weekdayLabels(string $locale, string $tz): array
    {
        return collect(range(0, 6))->map(
            fn (int $i) => CarbonImmutable::parse('2026-01-05', $tz)->locale($locale)->addDays($i)->minDayName,
        )->all();
    }

    /**
     * @return list<array{label:string,date:string,is_today:bool,entries:\Illuminate\Support\Collection<int, CalendarGridEntry>}>
     */
    private function buildWeekDays(string $locale): array
    {
        $tz = $this->appTz();
        $c = $this->cursorLocal();
        $ws = $c->locale($locale)->startOfWeek(CarbonInterface::MONDAY);
        $we = $c->locale($locale)->endOfWeek(CarbonInterface::SUNDAY);
        $startUtc = $ws->timezone($tz)->startOfDay()->utc();
        $endUtc = $we->timezone($tz)->endOfDay()->utc();
        $entries = $this->gridEntriesInUtcRange($startUtc, $endUtc);
        $today = CarbonImmutable::now($tz);

        $days = [];
        $day = $ws;
        while ($day->lessThanOrEqualTo($we)) {
            $local = $day->timezone($tz);
            $days[] = [
                'label' => $local->locale($locale)->translatedFormat('D, j M'),
                'date' => $local->toDateString(),
                'is_today' => $local->isSameDay($today),
                'entries' => $this->entriesForUtcDay($entries, $local),
            ];
            $day = $day->addDay();
        }

        return $days;
    }

    /**
     * @return array{year: int, months: list<array{title: string, cells: list<array<string, mixed>>}>, weekday_labels: list<string>}
     */
    private function buildYearModel(string $locale): array
    {
        $tz = $this->appTz();
        $year = $this->cursorLocal()->year;
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = CarbonImmutable::create($year, $m, 1, 0, 0, 0, $tz);
            $monthEnd = $monthStart->endOfMonth();
            $pad = $monthStart->dayOfWeekIso - 1;
            $daysInMonth = $monthEnd->day;
            $startUtc = $monthStart->timezone($tz)->startOfDay()->utc();
            $endUtc = $monthEnd->timezone($tz)->endOfDay()->utc();
            $entries = $this->gridEntriesInUtcRange($startUtc, $endUtc);

            $cells = [];
            for ($i = 0; $i < $pad; $i++) {
                $cells[] = ['empty' => true];
            }
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $local = CarbonImmutable::create($year, $m, $d, 0, 0, 0, $tz);
                $dayEntries = $this->entriesForUtcDay($entries, $local);
                $cells[] = [
                    'empty' => false,
                    'd' => $d,
                    'date' => $local->toDateString(),
                    'has_events' => $dayEntries->isNotEmpty(),
                    'tooltip' => $dayEntries->map(static fn (CalendarGridEntry $e) => $e->title)->implode('; '),
                ];
            }
            while (count($cells) % 7 !== 0) {
                $cells[] = ['empty' => true];
            }

            $months[] = [
                'title' => $monthStart->locale($locale)->translatedFormat('F'),
                'cells' => $cells,
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
            'weekday_labels' => self::weekdayLabels($locale, $tz),
        ];
    }
}
