<?php

declare(strict_types=1);

namespace App\Support\Calendar;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;

final readonly class CalendarGridEntry
{
    public function __construct(
        public string $sortKey,
        public CarbonImmutable $startsAtUtc,
        public CarbonImmutable $endsAtUtc,
        public string $title,
        public string $tooltip,
        public bool $isKanban,
        public bool $canEditCalendarEvent,
        public ?int $kanbanCardId = null,
        public ?int $calendarEventId = null,
        public ?string $kanbanKind = null,
        public bool $allDay = false,
        public ?string $colorHex = null,
    ) {}

    public static function fromCalendarEvent(CalendarEvent $e, bool $canEdit): self
    {
        $scopeLabel = $e->is_public ? __('ui.calendar.visibility_public') : __('ui.calendar.visibility_private');
        $tooltipLines = array_filter([
            $e->title,
            $scopeLabel,
            $e->description !== null && $e->description !== '' ? (string) $e->description : null,
        ]);

        $hex = $e->color;
        if ($hex !== null && $hex !== '' && ! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = null;
        }

        return new self(
            sortKey: 'cal-'.$e->id.'-'.$e->starts_at->timestamp,
            startsAtUtc: CarbonImmutable::parse($e->starts_at)->utc(),
            endsAtUtc: CarbonImmutable::parse($e->ends_at)->utc(),
            title: $e->title,
            tooltip: implode("\n", $tooltipLines),
            isKanban: false,
            canEditCalendarEvent: $canEdit,
            calendarEventId: $e->id,
            allDay: $e->all_day,
            colorHex: $hex,
        );
    }

    public static function kanbanPoint(
        string $kind,
        int $cardId,
        CarbonImmutable $atUtc,
        string $title,
        string $tooltip,
        ?string $sortKey = null,
    ): self {
        $t = $atUtc->startOfSecond();
        $key = $sortKey ?? 'kb-'.$kind.'-'.$cardId.'-'.$t->timestamp;

        return new self(
            sortKey: $key,
            startsAtUtc: $t,
            endsAtUtc: $t,
            title: $title,
            tooltip: $tooltip,
            isKanban: true,
            canEditCalendarEvent: false,
            kanbanCardId: $cardId,
            kanbanKind: $kind,
            allDay: false,
        );
    }
}
