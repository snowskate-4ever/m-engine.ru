# API Документация: Мультиканальная авторизация

## Обзор

Система поддерживает авторизацию пользователей через различные каналы:
- **Веб-сайт** - стандартная форма авторизации
- **Telegram** - авторизация через Telegram бота
- **API** - авторизация внешних систем
- **N8N Webhook** - интеграция с N8N для автоматизации

## Основные эндпоинты

### 1. Универсальная авторизация
```
POST /api/auth
```

Автоматически определяет канал авторизации по заголовкам и обрабатывает запрос соответствующим образом.

#### Заголовки
- `X-Auth-Channel-Type`: `web|telegram|api|n8n_webhook` - тип канала
- `X-Auth-Channel`: произвольное название канала для логирования

#### Примеры запросов

**Веб-авторизация:**
```bash
curl -X POST https://your-domain.com/api/auth \
  -H "Content-Type: application/json" \
  -H "X-Auth-Channel-Type: web" \
  -d '{
    "email": "user@example.com",
    "password": "secure_password"
  }'
```

**Telegram авторизация:**
```bash
curl -X POST https://your-domain.com/api/auth \
  -H "Content-Type: application/json" \
  -H "X-Auth-Channel-Type: telegram" \
  -d '{
    "telegram_id": 123456789,
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe",
    "chat_id": 123456789
  }'
```

**N8N Webhook авторизация:**
```bash
curl -X POST https://your-domain.com/api/webhooks/n8n/auth \
  -H "Content-Type: application/json" \
  -H "X-N8N-Signature: sha256=..." \
  -d '{
    "token": "auth_token_from_attempt",
    "email": "user@example.com",
    "name": "Test User",
    "callback_url": "https://n8n.your-domain.com/webhook/123"
  }'
```

### 2. Проверка статуса попытки авторизации
```
GET /api/auth/status/{attemptId}
```

Возвращает информацию о попытке авторизации по её ID.

#### Ответ
```json
{
  "status": "success",
  "user_id": 123,
  "channel": "web",
  "channel_type": "web",
  "expires_at": "2024-01-20T10:30:00Z",
  "created_at": "2024-01-20T10:00:00Z",
  "metadata": {
    "source": "direct"
  }
}
```

## Rate Limiting

- **Веб-авторизация**: 5 попыток в минуту
- **API авторизация**: 10 попыток в минуту
- **Telegram**: 3 попытки в минуту
- **N8N**: 60 попыток в минуту
- **Проверка статуса**: 30 запросов в минуту

## Модели данных

### AuthAttempt
Хранит информацию о каждой попытке авторизации:

- `channel`: название канала
- `channel_type`: тип канала (web, telegram, api, n8n_webhook)
- `user_id`: ID пользователя (после успешной авторизации)
- `ip_address`: IP адрес запроса
- `user_agent`: User-Agent браузера/API клиента
- `metadata`: дополнительные данные в JSON формате
- `status`: pending/success/failed/expired
- `auth_token`: уникальный токен для webhook авторизации
- `expires_at`: время истечения токена

### AuthChannel
Конфигурация каналов авторизации:

- `name`: уникальное название канала
- `type`: тип канала
- `config`: JSON конфигурация
- `is_active`: активен ли канал
- `webhook_url`: URL для обратных вызовов

### User (расширения)
Дополнительные поля для отслеживания источника регистрации:

- `registration_channel`: канал регистрации
- `registration_metadata`: метаданные регистрации (JSON)
- `telegram_id`: ID пользователя в Telegram

## Администрирование

### MoonShine панель
- **Попытки авторизации**: просмотр всех попыток с фильтрацией
- **Каналы авторизации**: управление активными каналами

### Консольные команды
```bash
# Очистка старых попыток авторизации
php artisan auth:cleanup --days=30

# Запуск планировщика (для автоматической очистки)
php artisan schedule:run
```

## Логирование

Все авторизационные события логируются в отдельный файл:
```
storage/logs/auth.log
```

Примеры логов:
```
[2024-01-20 10:00:00] auth.INFO: Auth attempt created {"attempt_id": 123, "channel": "web", "type": "web", "ip": "192.168.1.1"}
[2024-01-20 10:00:05] auth.INFO: N8N callback sent successfully {"user_id": 456, "attempt_id": 123, "callback_url": "https://n8n.example.com/webhook"}
```

## Безопасность

- **Rate limiting** по каналам авторизации
- **Валидация подписей** для N8N webhook
- **Истечение токенов** через 30 минут
- **Логирование** всех попыток авторизации
- **Автоматическая очистка** старых данных

## Тестирование

```bash
# Запуск всех тестов авторизации
php artisan test --filter=MultiChannelAuthTest

# Запуск конкретного теста
php artisan test --filter=test_web_authentication_creates_attempt_and_succeeds
```

## Переменные окружения

```env
# N8N Integration
N8N_WEBHOOK_SECRET=your_webhook_secret_here
N8N_ALLOWED_IPS=1.2.3.4,5.6.7.8
N8N_WORKFLOW_URL=https://n8n.your-domain.com
N8N_TIMEOUT=30
N8N_RETRY_ATTEMPTS=3

# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_WEBHOOK_SECRET=your_webhook_secret
TELEGRAM_AUTH_TIMEOUT=300

# Auth Channels
AUTH_DEFAULT_EXPIRY=1800
AUTH_CLEANUP_DAYS=30
AUTH_RATE_LIMIT_WEB=5
AUTH_RATE_LIMIT_API=10
AUTH_RATE_LIMIT_TELEGRAM=3
AUTH_RATE_LIMIT_N8N=60
AUTH_MAX_ATTEMPTS_PER_HOUR=20
AUTH_BLOCK_DURATION_MINUTES=15

# Logging
LOG_AUTH_DAYS=30
```

## Мониторинг

### Метрики для отслеживания
- Количество попыток авторизации по каналам
- Процент успешных авторизаций
- Время ответа API
- Количество заблокированных запросов (rate limiting)

### Уведомления
- Уведомления о подозрительной активности
- Отчеты о ежедневной статистике авторизаций