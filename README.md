# Task/Event/Resource/Type API (Laravel + Sanctum + Spatie Media Library)

Простое API с токен-аутентификацией, задачами с вложениями и CRUD для событий, ресурсов и типов.

## Запуск
- Скопировать `.env` и выдать ключи: `cp .env.example .env && php artisan key:generate`
- Установить зависимости: `composer install && npm install`
- Применить миграции: `php artisan migrate`  
  (для `Schema::change()` может понадобиться `composer require doctrine/dbal`)
- Создать symlink для файлов: `php artisan storage:link`
- Настроить почту в `.env` (`MAIL_...`), иначе отправка уведомлений упадёт.

## Аутентификация
- Токены Laravel Sanctum.
- Получить токен: `POST /api/login` с `email`, `password`.
- Использовать заголовок: `Authorization: Bearer <token>`.

## Задачи (Task)
Поля: `title`, `description`, `status` (`planned|in_progress|done`), `done_at` (опц.), `user_id` (исполнитель), `attachments[]` (опц., файлы до 10 МБ каждый).

Маршруты (`auth:sanctum`):
- `GET /api/tasks` — список с фильтрами `status`, `user_id`, `done_at`.
- `POST /api/tasks` — создать; multipart с `attachments[]`.
- `GET /api/tasks/{id}` — получить задачу.
- `PUT /api/tasks/{id}` — обновить; при передаче новых `attachments[]` коллекция заменяется.
- `DELETE /api/tasks/{id}` — удалить.

## События (Event) и Бронирования (Booking)
Поля: 
- `name` (unique, required) — название события/бронирования
- `description` (опц.) — описание
- `active` (bool, опц.) — активность, по умолчанию `true`
- `booking_resource_id` (int, опц.) — ID ресурса, который делает бронирование
- `booked_resource_id` (int, опц.) — ID забронированного ресурса (если указан, это бронирование)
- `room_id` (int, опц.) — ID комнаты для бронирования (только если указан `booked_resource_id`)
- `user_id` (int, опц.) — ID пользователя, создавшего бронирование (по умолчанию текущий пользователь)
- `status` (опц.) — статус: `pending`, `confirmed`, `cancelled`, `completed` (по умолчанию `pending`)
- `start_at` (datetime, опц.) — начало события/бронирования
- `end_at` (datetime, опц.) — окончание (должно быть позже `start_at`)
- `notes` (string, опц.) — примечания к бронированию
- `price` (decimal, опц.) — цена бронирования

**Важно**: Если указаны `booked_resource_id`, `start_at` и `end_at`, система автоматически использует `BookingService` для создания бронирования с проверкой доступности и валидацией пересечений времени.

Маршруты (`auth:sanctum`):
- `GET /api/events` — список событий/бронирований
  - Фильтры: `active`, `booked_resource_id`, `booking_resource_id`, `room_id`, `user_id`, `status`, `date_from`, `date_to`, `bookings_only` (только бронирования), `room_bookings_only` (только бронирования с комнатами)
- `POST /api/events` — создать событие/бронирование
  - Если указан `booked_resource_id` + `start_at` + `end_at` → создается бронирование с проверкой доступности
  - Автоматически проверяются пересечения времени
- `GET /api/events/{id}` — получить событие/бронирование
- `PUT /api/events/{id}` — обновить событие/бронирование
  - Для бронирований автоматически проверяется доступность при изменении времени/ресурса/комнаты
- `DELETE /api/events/{id}` — удалить (soft delete)

## Ресурсы (Resource)
Поля: `name` (unique), `description`, `active` (bool), `type_id` (int, обяз.), `start_at` (date), `end_at` (date, >= start_at).

Маршруты (`auth:sanctum`):
- `GET /api/resources` — фильтры `active`, `type_id`, `date_from`, `date_to`.
- `POST /api/resources` — создать.
- `GET /api/resources/{id}` — получить.
- `PUT /api/resources/{id}` — обновить (unique name c игнором текущего).
- `DELETE /api/resources/{id}` — удалить (soft delete).

## Типы (Type)
Поля: `name` (unique), `resource_type` (string, опц.), `description` (required).

Маршруты (`auth:sanctum`):
- `GET /api/types` — фильтр `resource_type`.
- `POST /api/types` — создать.
- `GET /api/types/{id}` — получить.
- `PUT /api/types/{id}` — обновить (unique name c игнором текущего).
- `DELETE /api/types/{id}` — удалить.

Ответы (`ApiService`):
```json
// Успех
{ "success": true, "errors": {}, "data": {...}, "codError": 0, "message": "..." }
// Ошибка
{ "success": false, "errors": {...}, "data": {}, "codError": <int>, "message": "..." }
```

Вложенные файлы: Spatie Media Library, коллекция `attachments`, хранение локально, в ответе — массив объектов `{ id, file_name, url, mime_type, size }`.

Email: при создании задачи отправляется письмо исполнителю (`TaskCreatedMail`) с ссылкой на вложение (если есть). Почта должна быть настроена.

## Скрипты
- `npm run dev` — Vite dev сервер
- `npm run build` — сборка фронтенда
- `php artisan test` — тесты

## Лицензия
MIT.
