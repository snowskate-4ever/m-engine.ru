<?php

declare(strict_types=1);

namespace App\Support\Calendar;

use App\Models\CalendarEvent;
use App\Models\Event;
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

    public static function fromDomainEvent(Event $event): self
    {
        $title = is_string($event->name) && $event->name !== ''
            ? $event->name
            : __('ui.calendar.event_untitled');
        $status = (string) ($event->status ?? 'pending');
        $kindLabel = match (true) {
            $event->isRoomBooking() => __('ui.calendar.kind_room_booking'),
            $event->isResourceBooking() => __('ui.calendar.kind_resource_booking'),
            $event->isBooking() => __('ui.calendar.kind_booking'),
            default => __('ui.calendar.kind_event'),
        };

        $tooltip = implode("\n", array_filter([
            $title,
            __('ui.status').': '.$status,
            __('ui.calendar.filter_kind_label').': '.$kindLabel,
            $event->description !== null && $event->description !== '' ? (string) $event->description : null,
        ]));

        return new self(
            sortKey: 'evt-'.$event->id.'-'.(int) $event->start_at?->timestamp,
            startsAtUtc: CarbonImmutable::parse($event->start_at)->utc(),
            endsAtUtc: CarbonImmutable::parse($event->end_at)->utc(),
            title: $title,
            tooltip: $tooltip,
            isKanban: false,
            canEditCalendarEvent: false,
            calendarEventId: $event->id,
            allDay: false,
            colorHex: self::eventStatusColor($status),
        );
    }

    private static function eventStatusColor(string $status): string
    {
        return match ($status) {
            'confirmed' => '#059669',
            'cancelled' => '#DC2626',
            'completed' => '#2563EB',
            default => '#D97706',
        };
    }
}
