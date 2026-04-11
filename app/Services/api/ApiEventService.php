<?php

namespace App\Services\api;

use App\Models\Event;
use App\Services\BookingService;
use App\Services\Music\MusicCalendarFeedService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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
            'event_kind' => 'sometimes|in:all,event,booking,room_booking,resource_booking',
            'owner_entity_type' => 'sometimes|string',
            'owner_entity_id' => 'sometimes|integer|min:1',
            'include_related_entities' => 'sometimes|boolean',
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

        $user = $request->user();
        if ($user === null) {
            return ApiService::errorResponse(
                'Требуется авторизация.',
                ApiService::PERMISSION_DENIED,
                [],
                401
            );
        }

        $tz = (string) config('app.timezone');
        $startUtc = isset($filters['date_from'])
            ? CarbonImmutable::parse((string) $filters['date_from'], $tz)->startOfDay()->utc()
            : CarbonImmutable::now($tz)->startOfMonth()->subMonth()->startOfDay()->utc();
        $endUtc = isset($filters['date_to'])
            ? CarbonImmutable::parse((string) $filters['date_to'], $tz)->endOfDay()->utc()
            : CarbonImmutable::now($tz)->endOfMonth()->addMonth()->endOfDay()->utc();

        $ownerEntity = null;
        if (isset($filters['owner_entity_type'], $filters['owner_entity_id'])) {
            $ownerEntity = (string) $filters['owner_entity_type'].'#'.(int) $filters['owner_entity_id'];
        }

        $serviceFilters = [
            'event_kind' => $filters['event_kind'] ?? MusicCalendarFeedService::EVENT_KIND_ALL,
            'owner_entity' => $ownerEntity ?? 'all_linked',
            'include_related_entities' => $filters['include_related_entities'] ?? true,
        ];
        foreach (['active', 'booked_resource_id', 'booking_resource_id', 'room_id', 'user_id', 'status', 'bookings_only', 'room_bookings_only'] as $legacyKey) {
            if (array_key_exists($legacyKey, $filters)) {
                $serviceFilters[$legacyKey] = $filters[$legacyKey];
            }
        }

        $events = app(MusicCalendarFeedService::class)
            ->eventsForRange($user, $startUtc, $endUtc, $serviceFilters)
            ->map(fn (Event $event) => self::formatEvent($event));

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

    public static function confirm_matching_booking(int $id, Request $request)
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

        $userId = (int) ($request->user()?->id ?? 0);
        $isOwner = (int) ($event->user_id ?? 0) === $userId;
        $isMusicOrganizer = (int) ($event->music_organizer_user_id ?? 0) === $userId;
        if (! $isOwner && ! $isMusicOrganizer) {
            return ApiService::errorResponse(
                'Недостаточно прав для подтверждения бронирования.',
                ApiService::PERMISSION_DENIED,
                [],
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'booked_resource_id' => ['required', 'integer', 'exists:resources,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'booking_resource_id' => ['nullable', 'integer', 'exists:resources,id'],
        ], [
            'booked_resource_id.required' => 'Необходимо указать ресурс для бронирования.',
            'booked_resource_id.exists' => 'Выбранный ресурс не существует.',
            'room_id.exists' => 'Выбранная комната не существует.',
            'booking_resource_id.exists' => 'Ресурс инициатора бронирования не существует.',
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

        try {
            $bookingService = new BookingService();
            $confirmed = $bookingService->confirmMatchingBooking(
                $event->id,
                (int) $data['booked_resource_id'],
                isset($data['room_id']) ? (int) $data['room_id'] : null,
                isset($data['booking_resource_id']) ? (int) $data['booking_resource_id'] : null,
            );

            return ApiService::successResponse('Бронирование подтверждено', self::formatEvent($confirmed));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiService::errorResponse(
                $e->getMessage(),
                ApiService::UNPROCESSABLE_CONTENT,
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            return ApiService::errorResponse(
                'Ошибка подтверждения бронирования: '.$e->getMessage(),
                ApiService::UNPROCESSABLE_CONTENT,
                [],
                422
            );
        }
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
            'matching_space_type' => $event->matching_space_type,
            'matching_space_id' => $event->matching_space_id,
            'matching_proposed_start_at' => $event->matching_proposed_start_at?->toISOString(),
            'matching_proposed_end_at' => $event->matching_proposed_end_at?->toISOString(),
            'matching_booking_confirmed_at' => $event->matching_booking_confirmed_at?->toISOString(),
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

