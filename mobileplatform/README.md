# M-Engine — мобильные клиенты

Здесь живут **нативные и кроссплатформенные** приложения. Бэкенд и веб остаются в корне репозитория (`m-engine.ru`).

## Что нужно для разработки на Kotlin (Android)

| Требование | Зачем |
|------------|--------|
| **JDK 17** (Temurin / Oracle) | Сборка Gradle и Kotlin |
| **Android Studio** (Ladybug / последний стабильный канал) | SDK, эмулятор, подпись APK, профилирование |
| **Android SDK** — через SDK Manager в Studio | `compileSdk` / платформенные API |
| **Устройство или эмулятор** API **26+** | Соответствует `minSdk` в проекте |

После установки откройте в Android Studio каталог:

`mobileplatform/android`

Первая синхронизация Gradle скачает зависимости. Сборка debug APK: **Build → Build APK(s)** или в терминале из `mobileplatform/android`:

```bash
.\gradlew.bat assembleDebug
```

APK: `app/build/outputs/apk/debug/app-debug.apk`.

## Связь с сервером

Контракт HTTP API мессенджера и push описан в репозитории:

- [`.cursor/docs/messenger-api.md`](../.cursor/docs/messenger-api.md) — базовый URL, Bearer, маршруты
- [`.cursor/docs/messenger-push-apns.md`](../.cursor/docs/messenger-push-apns.md) — FCM для Android
- [`.cursor/plans/2026-03-29-messenger-roadmap.md`](../.cursor/plans/2026-03-29-messenger-roadmap.md) — продуктовый контекст (IP + fallback на домен и т.д.)

Планируемые зависимости приложения (подключайте по мере экранов): **Retrofit** или **Ktor Client**, **Room** (кэш и очередь исходящих), **Firebase Cloud Messaging**, клиент **WebSocket** / **Pusher**-совместимый под Laravel Reverb — см. доку по Echo/Reverb на вебе и `messenger-api.md`.

## Структура

```
mobileplatform/
  README.md          — этот файл
  android/           — Kotlin + Jetpack Compose (один модуль app)
```

Дальше при появлении iOS или общего кода можно добавить, например, `mobileplatform/ios/` или `mobileplatform/shared/` (KMM) — без ломки текущего Android-модуля.
