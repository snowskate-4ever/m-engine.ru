# Task/Event/Resource API (Laravel + Sanctum + Spatie Media Library)

Простое API с токен-аутентификацией, задачами с вложениями и CRUD для событий и ресурсов.

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

## События (Event)
Поля: `name` (unique), `description`, `active` (bool), `resource_id` (uuid, опц.), `room_id` (uuid, опц.), `start_at`, `end_at` (>= start_at, опц.).

Маршруты (`auth:sanctum`):
- `GET /api/events` — фильтры `active`, `resource_id`, `room_id`, `date_from`, `date_to`.
- `POST /api/events` — создать.
- `GET /api/events/{id}` — получить.
- `PUT /api/events/{id}` — обновить (unique name c игнором текущего).
- `DELETE /api/events/{id}` — удалить (soft delete).

## Ресурсы (Resource)
Поля: `name` (unique), `description`, `active` (bool), `type_id` (int, обяз.), `start_at` (date), `end_at` (date, >= start_at).

Маршруты (`auth:sanctum`):
- `GET /api/resources` — фильтры `active`, `type_id`, `date_from`, `date_to`.
- `POST /api/resources` — создать.
- `GET /api/resources/{id}` — получить.
- `PUT /api/resources/{id}` — обновить (unique name c игнором текущего).
- `DELETE /api/resources/{id}` — удалить (soft delete).

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
