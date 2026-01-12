<?php

namespace App\Observers;

use App\Models\Event;
use Illuminate\Validation\ValidationException;

class EventObserver
{
    /**
     * Handle the Event "creating" event.
     * Проверяем доступность перед созданием бронирования
     */
    public function creating(Event $event): void
    {
        $this->checkAvailability($event);
    }

    /**
     * Handle the Event "updating" event.
     * Проверяем доступность перед обновлением бронирования
     */
    public function updating(Event $event): void
    {
        // Проверяем только если это бронирование и изменяются критические поля
        if (!$event->isBooking()) {
            return;
        }

        // Если статус меняется на cancelled, не проверяем доступность
        if ($event->isDirty('status') && $event->status === 'cancelled') {
            return;
        }

        // Проверяем доступность если изменяются критические поля
        if ($this->hasCriticalChanges($event)) {
            // Используем новые значения для проверки
            $resourceId = $event->booked_resource_id ?? $event->getOriginal('booked_resource_id');
            $roomId = $event->room_id ?? $event->getOriginal('room_id');
            $startAt = $event->start_at ?? $event->getOriginal('start_at');
            $endAt = $event->end_at ?? $event->getOriginal('end_at');

            if ($resourceId && $startAt && $endAt) {
                $isAvailable = Event::isAvailable(
                    $resourceId,
                    $startAt,
                    $endAt,
                    $roomId,
                    $event->getKey()
                );

                if (!$isAvailable) {
                    throw ValidationException::withMessages([
                        'time' => ['Выбранное время уже занято. Пожалуйста, выберите другое время.']
                    ]);
                }
            }
        }
    }

    /**
     * Проверка доступности ресурса/комнаты
     */
    protected function checkAvailability(Event $event): void
    {
        // Проверяем только бронирования (когда указан booked_resource_id и время)
        if (!$event->isBooking() || !$event->booked_resource_id || !$event->start_at || !$event->end_at) {
            return;
        }

        // Отмененные бронирования не проверяем
        if ($event->status === 'cancelled') {
            return;
        }

        $isAvailable = Event::isAvailable(
            $event->booked_resource_id,
            $event->start_at,
            $event->end_at,
            $event->room_id,
            $event->getKey() // Исключаем текущее событие при обновлении
        );

        if (!$isAvailable) {
            throw ValidationException::withMessages([
                'time' => ['Выбранное время уже занято. Пожалуйста, выберите другое время.']
            ]);
        }
    }

    /**
     * Проверка, изменились ли критические поля для бронирования
     */
    protected function hasCriticalChanges(Event $event): bool
    {
        return $event->isDirty(['booked_resource_id', 'room_id', 'start_at', 'end_at']);
    }

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "updated" event.
     */
    public function updated(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "deleted" event.
     */
    public function deleted(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "restored" event.
     */
    public function restored(Event $event): void
    {
        //
    }

    /**
     * Handle the Event "force deleted" event.
     */
    public function forceDeleted(Event $event): void
    {
        //
    }
}
