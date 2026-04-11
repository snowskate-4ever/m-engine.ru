<?php

namespace App\Models;

use App\Enums\MusicEventAssemblyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'active',
        'resource_id',
        'booking_resource_id',
        'booked_resource_id',
        'room_id',
        'user_id',
        'status',
        'start_at',
        'end_at',
        'notes',
        'price',
        'music_organizer_user_id',
        'concert_venue_id',
        'matching_space_type',
        'matching_space_id',
        'matching_proposed_start_at',
        'matching_proposed_end_at',
        'matching_booking_confirmed_at',
        'assembly_status',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'matching_proposed_start_at' => 'datetime',
        'matching_proposed_end_at' => 'datetime',
        'matching_booking_confirmed_at' => 'datetime',
        'price' => 'decimal:2',
        'assembly_status' => MusicEventAssemblyStatus::class,
    ];
    
    public function bookingResource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'booking_resource_id');
    }

    public function bookedResource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'booked_resource_id');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function musicOrganizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'music_organizer_user_id');
    }

    public function concertVenue(): BelongsTo
    {
        return $this->belongsTo(ConcertVenue::class);
    }

    public function matchingSpace(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'matching_space_type', 'matching_space_id');
    }

    public function peformers(): BelongsToMany
    {
        return $this->belongsToMany(Peformer::class, 'event_peformer')
            ->withPivot('added_via_search_request_id')
            ->withTimestamps();
    }

    public function organizerPerformerInvites(): HasMany
    {
        return $this->hasMany(OrganizerPerformerInvite::class);
    }

    public function organizerVenueInvites(): HasMany
    {
        return $this->hasMany(OrganizerVenueInvite::class);
    }

    public function organizerStudioInvites(): HasMany
    {
        return $this->hasMany(OrganizerStudioInvite::class);
    }

    public function organizerRehersalInvites(): HasMany
    {
        return $this->hasMany(OrganizerRehersalInvite::class);
    }

    public function organizerSchoolInvites(): HasMany
    {
        return $this->hasMany(OrganizerSchoolInvite::class);
    }

    /**
     * Scope для получения только бронирований
     */
    public function scopeBookings(Builder $query): Builder
    {
        return $query->whereNotNull('booked_resource_id');
    }

    /**
     * Scope для получения бронирований с комнатами
     */
    public function scopeRoomBookings(Builder $query): Builder
    {
        return $query->whereNotNull('room_id');
    }

    /**
     * Scope для получения бронирований ресурсов без комнат
     */
    public function scopeResourceBookings(Builder $query): Builder
    {
        return $query->whereNotNull('booked_resource_id')
                     ->whereNull('room_id');
    }

    /**
     * Scope для фильтрации по статусу
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Проверка, является ли событие бронированием
     */
    public function isBooking(): bool
    {
        return !is_null($this->booked_resource_id);
    }

    /**
     * Проверка, является ли бронирование комнаты
     */
    public function isRoomBooking(): bool
    {
        return $this->isBooking() && !is_null($this->room_id);
    }

    /**
     * Проверка, является ли бронирование ресурса (без комнаты)
     */
    public function isResourceBooking(): bool
    {
        return $this->isBooking() && is_null($this->room_id);
    }

    /**
     * Проверка доступности ресурса/комнаты в указанное время
     * 
     * @param int|null $resourceId ID ресурса
     * @param \DateTime|\Carbon\Carbon|string $startAt Начало периода
     * @param \DateTime|\Carbon\Carbon|string $endAt Конец периода
     * @param int|null $roomId ID комнаты (если null, проверяется весь ресурс)
     * @param int|null $excludeEventId ID события для исключения (при обновлении)
     * @return bool true если доступно, false если занято
     */
    public static function isAvailable(
        ?int $resourceId,
        $startAt,
        $endAt,
        ?int $roomId = null,
        ?int $excludeEventId = null
    ): bool {
        if (!$resourceId || !$startAt || !$endAt) {
            return false;
        }

        // Преобразуем в Carbon для удобства работы
        $start = $startAt instanceof \DateTime ? \Carbon\Carbon::instance($startAt) : \Carbon\Carbon::parse($startAt);
        $end = $endAt instanceof \DateTime ? \Carbon\Carbon::instance($endAt) : \Carbon\Carbon::parse($endAt);

        $query = static::where('booked_resource_id', $resourceId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($start, $end) {
                // Проверяем пересечения: новое бронирование пересекается с существующим
                $q->where(function ($subQ) use ($start, $end) {
                    // Новое начинается внутри существующего
                    $subQ->where('start_at', '<=', $start)
                         ->where('end_at', '>', $start);
                })->orWhere(function ($subQ) use ($start, $end) {
                    // Новое заканчивается внутри существующего
                    $subQ->where('start_at', '<', $end)
                         ->where('end_at', '>=', $end);
                })->orWhere(function ($subQ) use ($start, $end) {
                    // Новое полностью содержит существующее
                    $subQ->where('start_at', '>=', $start)
                         ->where('end_at', '<=', $end);
                })->orWhere(function ($subQ) use ($start, $end) {
                    // Существующее полностью содержит новое
                    $subQ->where('start_at', '<=', $start)
                         ->where('end_at', '>=', $end);
                });
            });

        // Если указана комната, проверяем только бронирования этой комнаты
        // Если комната не указана, проверяем все бронирования ресурса (включая комнаты)
        if ($roomId) {
            $query->where('room_id', $roomId);
        } else {
            // Если бронируем весь ресурс, проверяем что нет бронирований комнат в это время
            // и нет других бронирований всего ресурса
            $query->where(function ($q) {
                $q->whereNull('room_id') // Бронирования всего ресурса
                  ->orWhereNotNull('room_id'); // Или любые комнаты (они тоже блокируют ресурс)
            });
        }

        // Исключаем текущее событие при обновлении
        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }

        return $query->count() === 0;
    }
}
