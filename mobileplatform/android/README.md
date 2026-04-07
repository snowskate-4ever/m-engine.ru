# Android (M-Engine Mobile)

Сборка из каталога `mobileplatform/android` — общий контекст: [../README.md](../README.md).

## Сборки (flavors)

| Flavor | `applicationId` | `API_BASE_URL` по умолчанию |
|--------|-----------------|-----------------------------|
| **staging** | `ru.mengine.mobile.staging` | `https://m-engine.ru` (в `app/build.gradle.kts` можно заменить на стенд или `http://10.0.2.2:8000` для эмулятора) |
| **prod** | `ru.mengine.mobile` | `https://m-engine.ru` |

В Android Studio: **Build Variants** → выберите `staging` или `prod`.

### Ошибка «SDK location not found»

Нужен файл **`local.properties`** в `mobileplatform\android` со строкой `sdk.dir=...`.

- Скопируйте **`local.properties.example`** → **`local.properties`** и подставьте путь из Android Studio: *Settings → Android SDK → Android SDK location*.
- Либо задайте переменную среды **`ANDROID_HOME`** (или **`ANDROID_SDK_ROOT`**) на каталог SDK.

При использовании **`run-gradle.ps1`** файл создастся сам, если SDK лежит в `%LOCALAPPDATA%\Android\Sdk`.

### Терминал без JAVA_HOME (Windows)

Если `.\gradlew.bat` пишет *JAVA_HOME is not set*, из каталога `mobileplatform\android` выполните:

```powershell
.\run-gradle.ps1 assembleStagingDebug
```

Скрипт подставит JBR из Android Studio / SDK, если он установлен в стандартном месте. Либо один раз задайте пользовательскую переменную **`JAVA_HOME`** (например `C:\Program Files\Android\Android Studio\jbr`) и добавьте `%JAVA_HOME%\bin` в **PATH**.

## Вход

**POST /api/login** (email и пароль; сервер проверяет через Laravel `Auth::attempt`). Токен сохраняется в **DataStore** (для продакшена позже лучше зашифрованное хранилище).

## Следующие шаги разработки

1. **Список чатов** — `GET /api/messenger/conversations` с `Authorization: Bearer {token}`; добавить интерцептор Ktor для заголовка.
2. **Чат** — история и `POST …/messages`, поле `client_message_id`.
3. **Push** — FCM и `POST /api/devices/push-token` с `platform: android`.
4. **Realtime** — клиент под Laravel Reverb / Echo или poll, как в плане.
5. **Несколько базовых URL** — список IP и fallback на домен (roadmap §12).

Контракт API: `.cursor/docs/messenger-api.md` в корне монорепо.
