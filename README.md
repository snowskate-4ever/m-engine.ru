# Task API (Laravel + Sanctum + Spatie Media Library)

Простое API для задач с токен-аутентификацией, вложениями и уведомлением по email.

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
