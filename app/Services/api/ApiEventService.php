<?php

namespace App\Services\api;

use App\Models\Event;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiEventService
{
    public static function get_events(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'active' => 'sometimes|boolean',
            'booked_resource_id' => 'sometimes|integer|exists:resources,id',
            'booking_resource_id' => 'sometimes|integer|exists:resources,id',
            'room_id' => 'sometimes|integer|exists:rooms,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'status' => 'sometimes|in:pending,confirmed,cancelled,completed',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'bookings_only' => 'sometimes|boolean', // Только бронирования
            'room_bookings_only' => 'sometimes|boolean', // Только бронирования с комнатами
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте параметры фильтрации.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $filters = $validator->validated();

        $query = Event::query()->orderByDesc('start_at')->orderByDesc('created_at');

        if (array_key_exists('active', $filters)) {
            $query->where('active', $filters['active']);
        }
        if (isset($filters['booked_resource_id'])) {
            $query->where('booked_resource_id', $filters['booked_resource_id']);
        }
        if (isset($filters['booking_resource_id'])) {
            $query->where('booking_resource_id', $filters['booking_resource_id']);
        }
        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('start_at', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('end_at', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }
        if (isset($filters['bookings_only']) && $filters['bookings_only']) {
            $query->bookings();
        }
        if (isset($filters['room_bookings_only']) && $filters['room_bookings_only']) {
            $query->roomBookings();
        }

        $events = $query->with(['bookedResource', 'bookingResource', 'room', 'user'])->get()->map(fn (Event $event) => self::formatEvent($event));

        return ApiService::successResponse('Список событий получен', ['events' => $events]);
    }

    public static function create_event(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:events,name'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'booking_resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'booked_resource_id' => ['nullable', 'integer', 'exists:resources,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled,completed'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Событие с таким названием уже существует.',
            'booked_resource_id.exists' => 'Выбранный ресурс не существует.',
            'booking_resource_id.exists' => 'Выбранный ресурс для бронирования не существует.',
            'room_id.exists' => 'Выбранная комната не существует.',
            'user_id.exists' => 'Выбранный пользователь не существует.',
            'end_at.after' => 'Дата окончания должна быть позже даты начала.',
            'status.in' => 'Некорректный статус.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Цена не может быть отрицательной.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $data = $validator->validated();

        // Если указан booked_resource_id, это бронирование - используем BookingService
        if (isset($data['booked_resource_id']) && isset($data['start_at']) && isset($data['end_at'])) {
            try {
                $bookingService = new BookingService();
                $bookingData = [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'booked_resource_id' => $data['booked_resource_id'],
                    'room_id' => $data['room_id'] ?? null,
                    'user_id' => $data['user_id'] ?? auth()->id(),
                    'start_at' => $data['start_at'],
                    'end_at' => $data['end_at'],
                    'status' => $data['status'] ?? 'pending',
                    'notes' => $data['notes'] ?? null,
                    'price' => $data['price'] ?? null,
                    'active' => $data['active'] ?? true,
                ];
                
                if (isset($data['booking_resource_id'])) {
                    $bookingData['booking_resource_id'] = $data['booking_resource_id'];
                }
                
                $event = $bookingService->createBooking($bookingData);
                
                return ApiService::successResponse('Бронирование создано', self::formatEvent($event->load(['bookedResource', 'bookingResource', 'room', 'user'])));
            } catch (\Illuminate\Validation\ValidationException $e) {
                return ApiService::errorResponse(
                    $e->getMessage(),
                    ApiService::UNPROCESSABLE_CONTENT,
                    $e->errors(),
                    422
                );
            } catch (\Exception $e) {
                return ApiService::errorResponse(
                    'Ошибка при создании бронирования: ' . $e->getMessage(),
                    ApiService::UNPROCESSABLE_CONTENT,
                    [],
                    422
                );
            }
        }

        // Обычное событие (не бронирование)
        $event = new Event();
        $event->name = $data['name'];
        $event->description = $data['description'] ?? '';
        $event->active = $data['active'] ?? true;
        $event->booking_resource_id = $data['booking_resource_id'] ?? null;
        $event->booked_resource_id = $data['booked_resource_id'] ?? null;
        $event->room_id = $data['room_id'] ?? null;
        $event->user_id = $data['user_id'] ?? auth()->id();
        $event->status = $data['status'] ?? 'pending';
        $event->start_at = $data['start_at'] ?? null;
        $event->end_at = $data['end_at'] ?? null;
        $event->notes = $data['notes'] ?? null;
        $event->price = $data['price'] ?? null;
        $event->save();

        return ApiService::successResponse('Событие создано', self::formatEvent($event->load(['bookedResource', 'bookingResource', 'room', 'user'])));
    }

    public static function get_event(int $id)
    {
        $event = Event::with(['bookedResource', 'bookingResource', 'room', 'user'])->find($id);

        if (! $event) {
            return ApiService::errorResponse(
                'Событие не найдено.',
                ApiService::EVENT_NOT_FOUND,
                [],
                404
            );
        }

        return ApiService::successResponse('Событие получено', self::formatEvent($event));
    }

    public static function edit_event(int $id, Request $request)
    {
        $event = Event::find($id);

        if (! $event) {
            return ApiService::errorResponse(
                'Событие не найдено.',
                ApiService::EVENT_NOT_FOUND,
                [],
                404
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('events', 'name')->ignore($event->id)],
            'description' => ['sometimes', 'nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
            'booking_resource_id' => ['sometimes', 'nullable', 'integer', 'exists:resources,id'],
            'booked_resource_id' => ['sometimes', 'nullable', 'integer', 'exists:resources,id'],
            'room_id' => ['sometimes', 'nullable', 'integer', 'exists:rooms,id'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled,completed'],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after:start_at'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Название обязательно.',
            'name.unique' => 'Событие с таким названием уже существует.',
            'booked_resource_id.exists' => 'Выбранный ресурс не существует.',
            'booking_resource_id.exists' => 'Выбранный ресурс для бронирования не существует.',
            'room_id.exists' => 'Выбранная комната не существует.',
            'user_id.exists' => 'Выбранный пользователь не существует.',
            'end_at.after' => 'Дата окончания должна быть позже даты начала.',
            'status.in' => 'Некорректный статус.',
            'price.numeric' => 'Цена должна быть числом.',
            'price.min' => 'Цена не может быть отрицательной.',
        ]);

        if ($validator->fails()) {
            return ApiService::errorResponse(
                'Проверьте корректность введённых данных.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422
            );
        }

        $data = $validator->validated();

        // Если это бронирование и изменяются критические поля - используем BookingService
        if ($event->isBooking() && (isset($data['start_at']) || isset($data['end_at']) || isset($data['booked_resource_id']) || isset($data['room_id']))) {
            try {
                $bookingService = new BookingService();
                $event = $bookingService->updateBooking($id, $data);
                
                return ApiService::successResponse('Бронирование обновлено', self::formatEvent($event->load(['bookedResource', 'bookingResource', 'room', 'user'])));
            } catch (\Illuminate\Validation\ValidationException $e) {
                return ApiService::errorResponse(
                    $e->getMessage(),
                    ApiService::UNPROCESSABLE_CONTENT,
                    $e->errors(),
                    422
                );
            } catch (\Exception $e) {
                return ApiService::errorResponse(
                    'Ошибка при обновлении бронирования: ' . $e->getMessage(),
                    ApiService::UNPROCESSABLE_CONTENT,
                    [],
                    422
                );
            }
        }

        // Обычное обновление события
        if (array_key_exists('name', $data)) {
            $event->name = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $event->description = $data['description'];
        }
        if (array_key_exists('active', $data)) {
            $event->active = $data['active'];
        }
        if (array_key_exists('booking_resource_id', $data)) {
            $event->booking_resource_id = $data['booking_resource_id'];
        }
        if (array_key_exists('booked_resource_id', $data)) {
            $event->booked_resource_id = $data['booked_resource_id'];
        }
        if (array_key_exists('room_id', $data)) {
            $event->room_id = $data['room_id'];
        }
        if (array_key_exists('user_id', $data)) {
            $event->user_id = $data['user_id'];
        }
        if (array_key_exists('status', $data)) {
            $event->status = $data['status'];
        }
        if (array_key_exists('start_at', $data)) {
            $event->start_at = $data['start_at'];
        }
        if (array_key_exists('end_at', $data)) {
            $event->end_at = $data['end_at'];
        }
        if (array_key_exists('notes', $data)) {
            $event->notes = $data['notes'];
        }
        if (array_key_exists('price', $data)) {
            $event->price = $data['price'];
        }

        $event->save();

        return ApiService::successResponse('Событие обновлено', self::formatEvent($event->load(['bookedResource', 'bookingResource', 'room', 'user'])));
    }

    public static function delete_event(int $id)
    {
        $event = Event::find($id);

        if (! $event) {
            return ApiService::errorResponse(
                'Событие не найдено.',
                ApiService::EVENT_NOT_FOUND,
                [],
                404
            );
        }

        $event->delete();

        return ApiService::successResponse('Событие удалено');
    }

    protected static function formatEvent(Event $event): array
    {
        // Загружаем связи, если они не загружены
        if (!$event->relationLoaded('bookedResource')) {
            $event->load('bookedResource');
        }
        if (!$event->relationLoaded('bookingResource')) {
            $event->load('bookingResource');
        }
        if (!$event->relationLoaded('room')) {
            $event->load('room');
        }
        if (!$event->relationLoaded('user')) {
            $event->load('user');
        }

        return [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'active' => $event->active,
            'status' => $event->status ?? 'pending',
            'booking_resource_id' => $event->booking_resource_id,
            'booking_resource' => $event->bookingResource ? [
                'id' => $event->bookingResource->id,
                'name' => $event->bookingResource->name,
            ] : null,
            'booked_resource_id' => $event->booked_resource_id,
            'booked_resource' => $event->bookedResource ? [
                'id' => $event->bookedResource->id,
                'name' => $event->bookedResource->name,
            ] : null,
            'room_id' => $event->room_id,
            'room' => $event->room ? [
                'id' => $event->room->id,
                'name' => $event->room->name,
                'square' => $event->room->square,
            ] : null,
            'user_id' => $event->user_id,
            'user' => $event->user ? [
                'id' => $event->user->id,
                'name' => $event->user->name,
                'email' => $event->user->email,
            ] : null,
            'start_at' => $event->start_at?->toISOString(),
            'end_at' => $event->end_at?->toISOString(),
            'notes' => $event->notes,
            'price' => $event->price ? (float)$event->price : null,
            'is_booking' => $event->isBooking(),
            'is_room_booking' => $event->isRoomBooking(),
            'is_resource_booking' => $event->isResourceBooking(),
            'created_at' => $event->created_at?->toISOString(),
            'updated_at' => $event->updated_at?->toISOString(),
        ];
    }
}

