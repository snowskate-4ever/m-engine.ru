# Очереди Laravel: запуск и настройка

В проекте сбор постов из групп VK и скачивание медиа выполняются через очереди (queue). Задачи ставятся в очередь при нажатии «Запустить сбор постов» на странице `/admin/vk-posts`. Обрабатывает их воркер очередей.

## Что установлено

- Драйвер очередей по умолчанию: **database** (таблица `jobs` в БД).
- Очередь для задач VK: **vk** (используется `--queue=vk,default`).

Ничего дополнительно ставить не нужно: таблицы `jobs` и `failed_jobs` создаются миграциями Laravel.

## Запуск воркера вручную

В каталоге проекта на сервере выполните:

```bash
php artisan queue:work --queue=vk,default
```

Воркер будет обрабатывать задачи до остановки (Ctrl+C). Для фона используйте `nohup` или настройте Supervisor.

### Пример в фоне (nohup)

```bash
cd /path/to/m-engine.ru
nohup php artisan queue:work --queue=vk,default --sleep=3 --tries=3 > storage/logs/queue.log 2>&1 &
```

## Настройка Supervisor (рекомендуется на продакшене)

Supervisor держит воркер запущенным и перезапускает его при падении.

### 1. Установка Supervisor

**Ubuntu/Debian:**

```bash
sudo apt update
sudo apt install supervisor
```

**CentOS/RHEL:**

```bash
sudo yum install supervisor
```

### 2. Конфиг воркера

Создайте файл (например, `/etc/supervisor/conf.d/m-engine-queue.conf`):

```ini
[program:m-engine-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/m-engine.ru/artisan queue:work --queue=vk,default --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/m-engine.ru/storage/logs/queue.log
stopwaitsecs=3600
```

Замените:

- `/path/to/m-engine.ru` — полный путь к корню проекта (где `artisan`);
- `user=www-data` — пользователь, под которым крутится веб-сервер (nginx/apache).

### 3. Запуск и автозапуск

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start m-engine-queue:*
```

Проверка статуса:

```bash
sudo supervisorctl status m-engine-queue:*
```

Перезапуск после смены кода:

```bash
sudo supervisorctl restart m-engine-queue:*
```

## Переменные окружения (.env)

Для очередей можно задать (по желанию):

```env
QUEUE_CONNECTION=database
```

Если не задано, по умолчанию используется `database`. Таблица задаётся в `config/queue.php` (`jobs`).

## Просмотр неудачных задач

Неудачные задачи пишутся в таблицу `failed_jobs`. Повторить последнюю:

```bash
php artisan queue:retry latest
```

Повторить все:

```bash
php artisan queue:retry all
```

Очистить список неудачных:

```bash
php artisan queue:flush
```

## Краткая последовательность

1. Пользователь заходит на `/admin/vk-posts`, отмечает группы, нажимает «Запустить сбор постов».
2. В таблицу `jobs` добавляются задачи `FetchVkGroupPostsJob` для каждой группы.
3. Воркер (`queue:work --queue=vk,default`) забирает задачу, запрашивает посты у VK API, сохраняет в `vk_posts` и ставит в очередь `DownloadVkMediaJob` для каждого фото/аудио.
4. Воркер обрабатывает `DownloadVkMediaJob`: скачивает файл и сохраняет в `storage/app/vk-posts/...`.

После настройки Supervisor воркер будет работать постоянно и обрабатывать новые задачи без ручного запуска.
