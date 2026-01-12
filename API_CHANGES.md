# Изменения в API для событий/бронирований

## Обновлено: `ApiEventService`

### Изменения в полях

**Старые поля (устаревшие):**
- `resource_id` (uuid) - заменено на `booked_resource_id` (integer)

**Новые поля:**
- `booked_resource_id` (integer) - ID забронированного ресурса
- `booking_resource_id` (integer) - ID ресурса, который делает бронирование
- `room_id` (integer) - ID комнаты (вместо uuid)
- `user_id` (integer) - ID пользователя, создавшего бронирование
- `status` (enum) - статус: `pending`, `confirmed`, `cancelled`, `completed`
- `notes` (string) - примечания к бронированию
- `price` (decimal) - цена бронирования

### Изменения в методах

#### `get_events()`
- Добавлены фильтры: `booked_resource_id`, `booking_resource_id`, `user_id`, `status`, `bookings_only`, `room_bookings_only`
- Удален фильтр `resource_id` (используйте `booked_resource_id`)
- Автоматическая загрузка связей: `bookedResource`, `bookingResource`, `room`, `user`

#### `create_event()`
- Автоматическое определение типа: если указаны `booked_resource_id`, `start_at` и `end_at` → используется `BookingService` для создания бронирования
- Автоматическая проверка доступности и валидация пересечений времени
- Поддержка всех новых полей
- `user_id` по умолчанию берется из `auth()->id()`

#### `edit_event()`
- Для бронирований автоматически используется `BookingService` при изменении критических полей (время, ресурс, комната)
- Автоматическая проверка доступности при обновлении
- Поддержка всех новых полей

#### `formatEvent()`
- Возвращает расширенную информацию:
  - Объекты связанных ресурсов (`booked_resource`, `booking_resource`)
  - Объект комнаты (`room`) с площадью
  - Объект пользователя (`user`) с именем и email
  - Флаги типа бронирования (`is_booking`, `is_room_booking`, `is_resource_booking`)
  - Новые поля: `status`, `notes`, `price`

### Примеры запросов

#### Создание бронирования ресурса
```json
POST /api/events
{
  "name": "Бронирование зала",
  "description": "Описание",
  "booked_resource_id": 1,
  "start_at": "2026-01-15 10:00:00",
  "end_at": "2026-01-15 12:00:00",
  "status": "pending",
  "price": 5000.00,
  "notes": "Дополнительная информация"
}
```

#### Создание бронирования комнаты
```json
POST /api/events
{
  "name": "Бронирование комнаты",
  "booked_resource_id": 1,
  "room_id": 5,
  "start_at": "2026-01-15 14:00:00",
  "end_at": "2026-01-15 16:00:00"
}
```

#### Получение только бронирований
```
GET /api/events?bookings_only=true
```

#### Фильтрация по статусу
```
GET /api/events?status=confirmed
```

#### Фильтрация по ресурсу и дате
```
GET /api/events?booked_resource_id=1&date_from=2026-01-01&date_to=2026-01-31
```

### Формат ответа

```json
{
  "success": true,
  "errors": {},
  "data": {
    "id": 1,
    "name": "Бронирование зала",
    "description": "Описание",
    "active": true,
    "status": "pending",
    "booking_resource_id": null,
    "booking_resource": null,
    "booked_resource_id": 1,
    "booked_resource": {
      "id": 1,
      "name": "Название ресурса"
    },
    "room_id": 5,
    "room": {
      "id": 5,
      "name": "Комната 1",
      "square": 50.00
    },
    "user_id": 1,
    "user": {
      "id": 1,
      "name": "Иван Иванов",
      "email": "ivan@example.com"
    },
    "start_at": "2026-01-15T10:00:00.000000Z",
    "end_at": "2026-01-15T12:00:00.000000Z",
    "notes": "Дополнительная информация",
    "price": 5000.00,
    "is_booking": true,
    "is_room_booking": true,
    "is_resource_booking": false,
    "created_at": "2026-01-12T15:00:00.000000Z",
    "updated_at": "2026-01-12T15:00:00.000000Z"
  },
  "codError": 0,
  "message": "Бронирование создано"
}
```

### Обратная совместимость

⚠️ **Важно**: Поле `resource_id` больше не поддерживается. Используйте `booked_resource_id`.

Для миграции существующих клиентов:
1. Замените `resource_id` на `booked_resource_id`
2. Измените тип с `uuid` на `integer`
3. Обновите обработку ответов для новых полей

