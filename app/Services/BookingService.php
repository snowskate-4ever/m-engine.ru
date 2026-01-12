<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Resource;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Создать бронирование ресурса или комнаты
     *
     * @param array $data Данные бронирования
     * @return Event
     * @throws ValidationException
     */
    public function createBooking(array $data): Event
    {
        $validated = $this->validateBookingData($data);

        // Проверяем доступность
        if (!$this->checkAvailability(
            $validated['booked_resource_id'],
            $validated['start_at'],
            $validated['end_at'],
            $validated['room_id'] ?? null
        )) {
            throw ValidationException::withMessages([
                'time' => ['Выбранное время уже занято. Пожалуйста, выберите другое время.']
            ]);
        }

        // Создаем бронирование
        $event = new Event();
        $event->name = $validated['name'];
        $event->description = $validated['description'] ?? '';
        $event->active = $validated['active'] ?? true;
        $event->status = $validated['status'] ?? 'pending';
        $event->booked_resource_id = $validated['booked_resource_id'];
        $event->room_id = $validated['room_id'] ?? null;
        $event->user_id = $validated['user_id'] ?? auth()->id();
        $event->start_at = Carbon::parse($validated['start_at']);
        $event->end_at = Carbon::parse($validated['end_at']);
        $event->notes = $validated['notes'] ?? null;
        $event->price = $validated['price'] ?? null;
        $event->save();

        return $event->load(['bookedResource', 'room', 'user']);
    }

    /**
     * Обновить бронирование
     *
     * @param int $eventId ID бронирования
     * @param array $data Данные для обновления
     * @return Event
     * @throws ValidationException
     */
    public function updateBooking(int $eventId, array $data): Event
    {
        $event = Event::findOrFail($eventId);

        $validated = $this->validateBookingData($data, $eventId);

        // Проверяем доступность (исключая текущее бронирование)
        if (isset($validated['start_at']) || isset($validated['end_at']) || isset($validated['booked_resource_id']) || isset($validated['room_id'])) {
            $resourceId = $validated['booked_resource_id'] ?? $event->booked_resource_id;
            $roomId = $validated['room_id'] ?? $event->room_id;
            $startAt = isset($validated['start_at']) ? Carbon::parse($validated['start_at']) : $event->start_at;
            $endAt = isset($validated['end_at']) ? Carbon::parse($validated['end_at']) : $event->end_at;

            if (!$this->checkAvailability($resourceId, $startAt, $endAt, $roomId, $eventId)) {
                throw ValidationException::withMessages([
                    'time' => ['Выбранное время уже занято. Пожалуйста, выберите другое время.']
                ]);
            }
        }

        // Обновляем поля
        if (isset($validated['name'])) {
            $event->name = $validated['name'];
        }
        if (isset($validated['description'])) {
            $event->description = $validated['description'];
        }
        if (isset($validated['active'])) {
            $event->active = $validated['active'];
        }
        if (isset($validated['status'])) {
            $event->status = $validated['status'];
        }
        if (isset($validated['booked_resource_id'])) {
            $event->booked_resource_id = $validated['booked_resource_id'];
        }
        if (isset($validated['room_id'])) {
            $event->room_id = $validated['room_id'];
        }
        if (isset($validated['user_id'])) {
            $event->user_id = $validated['user_id'];
        }
        if (isset($validated['start_at'])) {
            $event->start_at = Carbon::parse($validated['start_at']);
        }
        if (isset($validated['end_at'])) {
            $event->end_at = Carbon::parse($validated['end_at']);
        }
        if (array_key_exists('notes', $validated)) {
            $event->notes = $validated['notes'];
        }
        if (array_key_exists('price', $validated)) {
            $event->price = $validated['price'];
        }

        $event->save();

        return $event->load(['bookedResource', 'room', 'user']);
    }

    /**
     * Отменить бронирование
     *
     * @param int $eventId ID бронирования
     * @return Event
     */
    public function cancelBooking(int $eventId): Event
    {
        $event = Event::findOrFail($eventId);
        $event->status = 'cancelled';
        $event->save();

        return $event;
    }

    /**
     * Подтвердить бронирование
     *
     * @param int $eventId ID бронирования
     * @return Event
     */
    public function confirmBooking(int $eventId): Event
    {
        $event = Event::findOrFail($eventId);
        $event->status = 'confirmed';
        $event->save();

        return $event;
    }

    /**
     * Проверить доступность ресурса/комнаты в указанное время
     *
     * @param int $resourceId ID ресурса
     * @param \DateTime|Carbon|string $startAt Начало периода
     * @param \DateTime|Carbon|string $endAt Конец периода
     * @param int|null $roomId ID комнаты (опционально)
     * @param int|null $excludeEventId ID события для исключения
     * @return bool true если доступно
     */
    public function checkAvailability(
        int $resourceId,
        $startAt,
        $endAt,
        ?int $roomId = null,
        ?int $excludeEventId = null
    ): bool {
        // Проверяем существование ресурса
        if (!Resource::find($resourceId)) {
            return false;
        }

        // Если указана комната, проверяем что она принадлежит ресурсу
        if ($roomId) {
            $room = Room::where('id', $roomId)
                ->where('resource_id', $resourceId)
                ->first();

            if (!$room) {
                return false;
            }
        }

        // Используем метод модели для проверки доступности
        return Event::isAvailable(
            $resourceId,
            $startAt instanceof \DateTime ? $startAt : Carbon::parse($startAt)->toDateTime(),
            $endAt instanceof \DateTime ? $endAt : Carbon::parse($endAt)->toDateTime(),
            $roomId,
            $excludeEventId
        );
    }

    /**
     * Получить доступные временные слоты для ресурса/комнаты
     *
     * @param int $resourceId ID ресурса
     * @param Carbon $date Дата для проверки
     * @param int|null $roomId ID комнаты (опционально)
     * @return array Массив доступных временных слотов
     */
    public function getAvailableTimeSlots(int $resourceId, Carbon $date, ?int $roomId = null): array
    {
        // Получаем все занятые периоды на эту дату
        $bookings = Event::where('booked_resource_id', $resourceId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('start_at', $date->toDateString())
            ->when($roomId, function ($query) use ($roomId) {
                return $query->where('room_id', $roomId);
            })
            ->get();

        // Формируем список занятых периодов
        $busySlots = [];
        foreach ($bookings as $booking) {
            $busySlots[] = [
                'start' => Carbon::parse($booking->start_at),
                'end' => Carbon::parse($booking->end_at),
            ];
        }

        // Здесь можно добавить логику формирования доступных слотов
        // Например, разбить день на интервалы по 30 минут и проверить каждый

        return $busySlots;
    }

    /**
     * Валидация данных бронирования
     *
     * @param array $data Данные для валидации
     * @param int|null $eventId ID события для исключения при обновлении
     * @return array Валидированные данные
     * @throws ValidationException
     */
    protected function validateBookingData(array $data, ?int $eventId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled,completed'],
            'booked_resource_id' => ['required', 'exists:resources,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'start_at' => ['required', 'date', 'after_or_equal:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];

        // При обновлении делаем поля опциональными
        if ($eventId) {
            $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];
            $rules['booked_resource_id'] = ['sometimes', 'required', 'exists:resources,id'];
            $rules['start_at'] = ['sometimes', 'required', 'date'];
            $rules['end_at'] = ['sometimes', 'required', 'date', 'after:start_at'];
        }

        $validator = Validator::make($data, $rules, [
            'name.required' => 'Название бронирования обязательно.',
            'booked_resource_id.required' => 'Необходимо указать ресурс для бронирования.',
            'booked_resource_id.exists' => 'Выбранный ресурс не существует.',
            'room_id.exists' => 'Выбранная комната не существует.',
            'user_id.exists' => 'Выбранный пользователь не существует.',
            'start_at.required' => 'Необходимо указать время начала бронирования.',
            'start_at.date' => 'Время начала должно быть корректной датой.',
            'start_at.after_or_equal' => 'Время начала не может быть в прошлом.',
            'end_at.required' => 'Необходимо указать время окончания бронирования.',
            'end_at.date' => 'Время окончания должно быть корректной датой.',
            'end_at.after' => 'Время окончания должно быть позже времени начала.',
            'status.in' => 'Некорректный статус бронирования.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Цена не может быть отрицательной.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }
}

