# M-Engine Flutter

Flutter-клиент, который движется к паритету с сайтом и уже повторяет часть текущего функционала:

- вход через `POST /api/login`
- проверка сессии через `GET /api/messenger/conversations`
- вкладки: Profiles, Resources, Planning, Messenger, Work, More
- чтение/отправка сообщений в мессенджере
- задачи (`/api/tasks`): список, создание, смена статуса
- события (`/api/events`): список, создание

## Что уже реализовано

- ручной каркас Flutter-проекта в `mobileplatform/flutter`
- API-слой для ключевых эндпоинтов из Android-версии
- хранение токена в `shared_preferences`
- базовый UI с login + shell и 6 вкладками
- заготовка roadmap для модулей сайта: blog, payments/contracts, integrations, AI, calendar sync

## Запуск

1. Установите Flutter SDK (stable channel) и добавьте `flutter` в `PATH`.
2. В каталоге `mobileplatform/flutter` выполните:

```powershell
flutter pub get
flutter run
```

## Настройка base URL

По умолчанию используется `https://m-engine.ru`.  
При необходимости смените `baseUrl` в `lib/src/services/api_client.dart`.

## Дальше (приоритет)

1. Добавить realtime для мессенджера (WebSocket/Reverb), чтобы уйти от ручного refresh.
2. Добавить push-токен endpoint `POST /api/devices/push-token`.
3. Добавить offline кэш/очередь сообщений.
4. Вынести env в flavor-конфигурацию (`staging` / `prod`).
