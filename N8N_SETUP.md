# Настройка доступа к n8n через веб-интерфейс

## Текущая ситуация

✅ **Что уже настроено:**
- n8n установлен и работает в Docker контейнерах
- Caddy веб-сервер настроен и работает на портах 80 и 443
- Caddy проксирует запросы на n8n контейнер (n8n:5678)
- Конфигурация Caddy использует домен: `n8n.madmdfactory.ru`
- Файрвол разрешает порты 80 и 443
- n8n доступен из контейнера Caddy (проверено)

❌ **Что нужно настроить:**
- DNS запись для домена `n8n.madmdfactory.ru` не настроена (NXDOMAIN)
- Из-за этого Caddy не может получить SSL сертификат от Let's Encrypt
- Домен недоступен извне

## Решение

### Вариант 1: Настроить DNS для поддомена n8n.madmdfactory.ru (рекомендуется)

1. **Настроить DNS A-запись:**
   - Зайти в панель управления DNS для домена `madmdfactory.ru`
   - Добавить A-запись:
     - **Имя/Поддомен:** `n8n`
     - **Тип:** `A`
     - **Значение/IP:** `80.93.60.187`
     - **TTL:** `3600` (или по умолчанию)

2. **Дождаться распространения DNS** (обычно 5-30 минут)

3. **Проверить DNS:**
   ```bash
   nslookup n8n.madmdfactory.ru
   # или
   dig n8n.madmdfactory.ru
   ```

4. **Перезапустить Caddy** (если нужно):
   ```bash
   docker restart caddy
   ```

5. **Проверить логи Caddy** для получения SSL сертификата:
   ```bash
   docker logs caddy --tail 50
   ```

6. **Доступ к n8n:**
   - После настройки DNS и получения SSL сертификата доступ будет по адресу:
   - `https://n8n.madmdfactory.ru`

### Вариант 2: Использовать основной домен madfactory.ru

Если нужно использовать `madfactory.ru` вместо поддомена:

1. **Изменить конфигурацию .env:**
   ```bash
   cd /root/n8n-install
   # Отредактировать .env файл
   nano .env
   # Изменить строку:
   N8N_HOSTNAME="madfactory.ru"
   ```

2. **Настроить DNS для madfactory.ru:**
   - В панели управления DNS изменить A-запись для `madfactory.ru`:
     - **Тип:** `A`
     - **Значение/IP:** `80.93.60.187`

3. **Перезапустить контейнеры:**
   ```bash
   cd /root/n8n-install
   docker-compose restart caddy
   ```

4. **Доступ к n8n:**
   - `https://madfactory.ru`

### Вариант 3: Использовать другой поддомен (например, n8n.madfactory.ru)

Если домен `madmdfactory.ru` недоступен, можно использовать `madfactory.ru`:

1. **Изменить .env:**
   ```bash
   cd /root/n8n-install
   nano .env
   # Изменить:
   N8N_HOSTNAME="n8n.madfactory.ru"
   ```

2. **Настроить DNS:**
   - Добавить A-запись для `n8n.madfactory.ru` → `80.93.60.187`

3. **Перезапустить Caddy:**
   ```bash
   docker-compose restart caddy
   ```

## Проверка текущего состояния

### Проверить DNS:
```bash
nslookup n8n.madmdfactory.ru
dig n8n.madmdfactory.ru
```

### Проверить статус n8n:
```bash
docker ps | grep n8n
docker logs n8n --tail 20
```

### Проверить статус Caddy:
```bash
docker ps | grep caddy
docker logs caddy --tail 30
```

### Проверить доступность n8n локально:
```bash
docker exec caddy curl -s http://n8n:5678 | head -20
```

### Проверить порты:
```bash
netstat -tlnp | grep -E ':(80|443|5678)'
# или
ss -tlnp | grep -E ':(80|443|5678)'
```

## Дополнительная информация

- **IP сервера:** 80.93.60.187
- **Порты:** 80 (HTTP), 443 (HTTPS)
- **Веб-сервер:** Caddy 2
- **n8n порт:** 5678 (внутри Docker сети)
- **Конфигурация:** `/root/n8n-install/docker-compose.yml`
- **Caddyfile:** `/root/n8n-install/Caddyfile`
- **Переменные окружения:** `/root/n8n-install/.env`

## Важные замечания

1. **SSL сертификаты:** Caddy автоматически получает SSL сертификаты от Let's Encrypt после настройки DNS
2. **DNS распространение:** После изменения DNS записей может потребоваться время (5-30 минут) для распространения изменений
3. **Файрвол:** Убедитесь, что порты 80 и 443 открыты (уже настроено)
4. **WEBHOOK_URL:** В конфигурации n8n уже настроен `WEBHOOK_URL=https://n8n.madmdfactory.ru/`

## Следующие шаги

1. ✅ Настроить DNS запись для выбранного домена
2. ✅ Дождаться распространения DNS
3. ✅ Проверить получение SSL сертификата в логах Caddy
4. ✅ Открыть `https://n8n.madmdfactory.ru` (или выбранный домен) в браузере
5. ✅ Создать учетную запись администратора в n8n (при первом входе)
