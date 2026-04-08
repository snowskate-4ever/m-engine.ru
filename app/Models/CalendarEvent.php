<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    public const REMINDER_OPTIONS = [10, 15, 30, 45, 60];

    /** @var list<string> */
    public const COLOR_PRESETS = [
        '#7C3AED',
        '#2563EB',
        '#059669',
        '#D97706',
        '#DC2626',
        '#DB2777',
        '#0891B2',
        '#4F46E5',
    ];

    public const DEFAULT_EVENT_COLOR = '#7C3AED';

    protected $fillable = [
        'user_id',
        'created_by',
        'is_public',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'reminder_minutes',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'all_day' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, CalendarEvent>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, CalendarEvent>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reminderAt(): ?CarbonImmutable
    {
        if ($this->reminder_minutes === null) {
            return null;
        }

        $tz = (string) config('app.timezone');

        if ($this->all_day) {
            $anchor = $this->starts_at->timezone($tz)->startOfDay()->setTime(8, 0);
        } else {
            $anchor = $this->starts_at->timezone($tz);
        }

        return $anchor->subMinutes((int) $this->reminder_minutes);
    }
}
