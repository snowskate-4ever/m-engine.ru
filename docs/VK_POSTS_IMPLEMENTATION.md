# Сбор постов из групп VK: что сделано и как запускать

## 1. Что сделано (подробно)

### 1.1. База данных (миграции)

- **2026_02_01_120000_create_vk_posts_table.php**  
  Таблица `vk_posts`: сохранённые посты из групп VK.  
  Поля: `id`, `vk_tracking_id` (связь с группой), `vk_post_id` (id поста в VK), `from_id`, `signer_id`, `text`, `raw_json` (полный сырой пост; после обработки можно очистить), `posted_at`, `processed_at` (nullable; когда пост обработан), `created_at`, `updated_at`.  
  Уникальность: пара `(vk_tracking_id, vk_post_id)` — один пост не сохраняется дважды.

- **2026_02_01_120001_create_vk_post_media_table.php**  
  Таблица `vk_post_media`: медиа вложений постов (фото, аудио).  
  Поля: `id`, `vk_post_id`, `type` (photo/audio), `vk_url`, `path` (локальный путь после скачивания), `sort_order`, `created_at`, `updated_at`.

- **2026_02_01_120002_add_next_from_to_vk_trackings_table.php**  
  В таблицу `vk_trackings` добавлено поле `next_from` (string, nullable) для пагинации wall.get (продолжение с места последнего запроса).

### 1.2. Модели

- **VkPost** (`app/Models/VkPost.php`): связь с `VkTracking` и `VkPostMedia`, scope `processed`/`unprocessed`, метод `clearRawAfterProcessed()` для очистки `raw_json` после обработки.
- **VkPostMedia** (`app/Models/VkPostMedia.php`): связь с `VkPost`.
- **VkTracking** (`app/Models/VkTracking.php`): добавлены `next_from` в fillable и связь `vkPosts()`.

### 1.3. Хранилище медиа

- В **config/filesystems.php** добавлен диск **vk_posts**: `storage/app/vk-posts`.  
  Файлы сохраняются по путям вида: `{vk_tracking_id}/{vk_post_id}/photo_0.jpg`, `audio_0.mp3` и т.д.

### 1.4. VK API

- В **app/Services/api/VkApiService.php** добавлен метод **getWallPosts()**: вызов VK API `wall.get` с параметрами `owner_id`, `count`, `offset`/`start_from`, `extended=1`. Возвращает `items` (массив постов) и `next_from` для пагинации.

### 1.5. Очереди (Jobs в App\Services)

- **FetchVkGroupPostsJob** (`app/Services/FetchVkGroupPostsJob.php`):  
  Принимает `vk_tracking_id`, `user_id`, `count`, `startFrom`. Берёт VK-токен пользователя из БД, вызывает `wall.get` для группы, для каждого нового поста создаёт запись в `vk_posts` (с `raw_json`), разбирает вложения (photo, audio), создаёт записи в `vk_post_media` и ставит в очередь **DownloadVkMediaJob** для каждого вложения. Сохраняет `next_from` в `vk_trackings`.

- **DownloadVkMediaJob** (`app/Services/DownloadVkMediaJob.php`):  
  Принимает `vk_post_media_id`. Скачивает файл по `vk_url`, сохраняет на диск `vk_posts`, обновляет поле `path` в `vk_post_media`.

### 1.6. Контроллер и маршруты

- **VkPostsController** (`app/Http/Controllers/VkPostsController.php`):  
  - **index()**: страница списка групп (VkTracking) и кнопки «Запустить сбор постов». Требуется авторизация; для постановки задач нужен VK-токен у пользователя.  
  - **fetch()**: POST; принимает `vk_tracking_ids[]` и `count`; ставит в очередь `FetchVkGroupPostsJob` для каждой выбранной группы (middleware `auth`).

- **routes/web.php**: добавлены маршруты под `auth`:  
  - `GET /admin/vk-posts` → `admin.vk-posts.index`  
  - `POST /admin/vk-posts/fetch` → `admin.vk-posts.fetch`

### 1.7. Представление

- **resources/views/vk_posts_index.blade.php**: страница выбора групп (чекбоксы), счётчик сохранённых постов по группам, поле «сколько постов за запрос» (1–100), кнопка «Запустить сбор постов», подсказка про воркер очередей.

### 1.8. Команда очистки сырых данных

- **VkClearProcessedRawCommand** (`app/Console/Commands/VkClearProcessedRawCommand.php`):  
  Команда `php artisan vk:clear-processed-raw` — обнуляет `raw_json` у постов, у которых заполнен `processed_at`. Опция `--dry-run` — только показать количество постов без изменений.

### 1.9. Дополнительные изменения (VK-тест и конфиг)

- **TestController**: редиректы после OAuth изменены с `admin.test` на `admin.vktest`; в `openApiIndex` передаются `vkOAuthTokenSaved` и `vkApiError`; на странице теста добавлена кнопка «Войти через OAuth» и кнопка «Получить группы (через сервер)» после OAuth.
- **TestService**: добавлен тест **testVkOpenApiConfig()** — проверка наличия `VK_APP_ID` и `VK_PROTECTED_KEY` в конфиге; результат выводится в блоке системных тестов на `/admin/vktest`.
- **resources/views/test/openapi.blade.php**: подсказка при ошибке «Выбранный способ авторизации не доступен»; блок «Получить группы (через сервер)» после OAuth.
- **.env.example**: добавлены комментарии для переменных VK Open API и команды очистки сырых данных (при необходимости).

### 1.10. Документация

- **docs/QUEUE.md**: инструкция по запуску воркера очередей (вручную, nohup, Supervisor), просмотр и повторы failed jobs.
- **docs/VK_POSTS_IMPLEMENTATION.md**: этот файл — описание реализации и инструкции.

---

## 2. Инструкции: что и как запускать

### 2.1. Первый запуск (один раз)

1. **Миграции** (на сервере в корне проекта):
   ```bash
   php artisan migrate
   ```
   Создаются таблицы `vk_posts`, `vk_post_media` и поле `next_from` в `vk_trackings`.

2. **VK-токен для пользователя** (чтобы ставить задачи сбора):
   - Зайти на сайт под учётной записью.
   - Открыть **https://ваш-сайт/admin/vktest**.
   - Нажать **«Войти через OAuth»** и авторизоваться в VK.  
   Токен сохранится в профиле пользователя (поле `vk_access_token`).

3. **Папка для медиа** (обычно создаётся автоматически при первой записи; при необходимости):
   ```bash
   mkdir -p storage/app/vk-posts
   chmod 775 storage/app/vk-posts
   ```

### 2.2. Запуск сбора постов (каждый раз, когда нужно собрать посты)

1. Зайти на **https://ваш-сайт/admin/vk-posts** (обязательно под пользователем с VK-токеном).
2. Отметить нужные группы (из списка VkTracking).
3. Указать «Сколько постов за один запрос» (1–100).
4. Нажать **«Запустить сбор постов»**.  
   Задачи появятся в очереди (таблица `jobs`).

5. **Обязательно** запустить воркер очередей (см. ниже), иначе задачи не выполнятся.

### 2.3. Запуск воркера очередей (обработка задач)

**Вариант A — вручную (для проверки):**
```bash
cd /path/to/m-engine.ru
php artisan queue:work --queue=vk,default
```
Воркер работает до остановки (Ctrl+C). Обрабатывает задачи из очередей `vk` и `default`.

**Вариант B — в фоне (nohup):**
```bash
cd /path/to/m-engine.ru
nohup php artisan queue:work --queue=vk,default --sleep=3 --tries=3 > storage/logs/queue.log 2>&1 &
```

**Вариант C — через Supervisor (рекомендуется на продакшене):**  
См. **docs/QUEUE.md** (установка Supervisor, конфиг, команды `supervisorctl`).

### 2.4. Очистка сырых данных после обработки постов

Когда посты помечены как обработанные (`processed_at` заполнен) и сырые данные больше не нужны:

```bash
php artisan vk:clear-processed-raw
```

Сначала посмотреть, сколько постов будет затронуто (без изменений в БД):

```bash
php artisan vk:clear-processed-raw --dry-run
```

### 2.5. Неудачные задачи (failed jobs)

- Повторить последнюю: `php artisan queue:retry latest`
- Повторить все: `php artisan queue:retry all`
- Очистить список: `php artisan queue:flush`  
Подробнее — в **docs/QUEUE.md**.

---

## 3. Краткая схема работы

1. Пользователь на `/admin/vk-posts` выбирает группы и нажимает «Запустить сбор постов».
2. Контроллер ставит в очередь задачу **FetchVkGroupPostsJob** для каждой группы (с текущим `user_id`).
3. Воркер выполняет **FetchVkGroupPostsJob**: запрос к VK API `wall.get`, сохранение новых постов в `vk_posts`, создание записей в `vk_post_media` и постановка **DownloadVkMediaJob** для каждого фото/аудио.
4. Воркер выполняет **DownloadVkMediaJob**: скачивание файла и сохранение в `storage/app/vk-posts/...`, обновление `path` в `vk_post_media`.
5. При необходимости после своей «обработки» постов (например, ИИ) выставить `processed_at` и выполнить `php artisan vk:clear-processed-raw` для освобождения места от `raw_json`.

---

## 4. Файлы, добавленные или изменённые (список для деплоя)

**Новые файлы:**  
- database/migrations/2026_02_01_120000_create_vk_posts_table.php  
- database/migrations/2026_02_01_120001_create_vk_post_media_table.php  
- database/migrations/2026_02_01_120002_add_next_from_to_vk_trackings_table.php  
- app/Models/VkPost.php  
- app/Models/VkPostMedia.php  
- app/Services/FetchVkGroupPostsJob.php  
- app/Services/DownloadVkMediaJob.php  
- app/Http/Controllers/VkPostsController.php  
- app/Console/Commands/VkClearProcessedRawCommand.php  
- resources/views/vk_posts_index.blade.php  
- docs/QUEUE.md  
- docs/VK_POSTS_IMPLEMENTATION.md  

**Изменённые файлы:**  
- app/Models/VkTracking.php  
- app/Services/TestService.php  
- app/Services/api/VkApiService.php  
- app/Http/Controllers/TestController.php  
- config/filesystems.php  
- routes/web.php  
- resources/views/test/openapi.blade.php  
- .env.example  

После деплоя на сервере выполнить: `php artisan migrate`.
