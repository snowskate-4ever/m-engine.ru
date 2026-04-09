# Музыкальная экосистема: спецификация и чеклист внедрения

Дальнейшие эпики после закрытия чеклиста — в **[music-ecosystem-roadmap.md](./music-ecosystem-roadmap.md)** (уведомления, публичный каталог, магазины, MoonShine, наблюдаемость и т.д.).

Обновлено: 2026-04-10.

## Продуктовые решения (зафиксировано)

- **Хаб аккаунта** (`/u/{slug}`): не делаем в текущей итерации.
- **Музыкант**: один профиль на аккаунт (`User` 1—1 `Musician`), инструменты через `musician_instrument`; при активации профиля нужен минимум один инструмент.
- **Performer** = коллектив / сольный проект / другое; таблица `peformers`. Типы: `band`, `solo_project`, `other`. У коллектива может не быть участников-музыкантов (оба сценария).
- **Один музыкант** может состоять в **нескольких** performer’ах; связь — отдельная запись; отдельные «роли в группе» не нужны.
- **Владелец** performer’а один; **администраторов** много; админ может не быть в составе.
- **Приглашения** в состав: с **подтверждением** музыкантом; пока только **внутри сайта** (чат, уведомления); закладываем расширение на e-mail / внешний контакт.
- **Учитель**: отдельная сущность, привязка к `User`, один профиль учителя на аккаунт; может не быть музыкантом; «доступен в других городах» + список городов (`teacher_city`).
- **Публичные страницы** (префиксы URL, **slug уникален в рамках типа**):
  - `/musicians/{slug}`
  - `/teachers/{slug}`
  - `/performers/{slug}`
  - `/studios/{slug}`
  - `/rehearsals/{slug}`
  - `/schools/{slug}`
  - `/shops/{slug}`
  - `/labels/{slug}`
  - `/producer-centers/{slug}`
- **Выключенная публикация**: тот же URL, контент — **заглушка «страница скрыта»** (не 404).
- **Настройка отображения**: только **предопределённые блоки** (вкл/выкл, порядок); **черновик** и **опубликованная** версия (JSON в `layout_draft` / `layout_published`).
- **Юр / физ лицо**: минимальный набор полей для юрлица; что показывать гостям — через настройки блоков страницы.
- **География**: несколько **адресов** на сущность (`Address` morph).
- **Модерация**: минимальный контур — поля **`moderation_hidden_at` / `moderation_reason`** на публичных музыких сущностях и магазинах; отдельная заглушка **`public.profiles.moderation_hidden`** (приоритетнее «владелец скрыл»). Управление в MoonShine. Жалобы/очередь «на проверке» — в roadmap §5 как расширение.

## Техническая модель (целевая)

| Сущность   | Владелец / связь с User | Публичные поля (минимум) |
|------------|-------------------------|---------------------------|
| Musician   | `user_id` (unique)      | `slug`, `public_page_enabled`, layouts |
| Teacher    | `user_id` (unique)      | то же + юр. поля, `available_other_cities` |
| Peformer   | `owner_user_id`         | то же + `performer_kind` |
| Studio     | `owner_user_id`       | то же + юр. поля |
| Rehersal   | `owner_user_id`       | то же + юр. поля |
| School     | `owner_user_id`       | то же + юр. поля |
| Shop       | `owner_user_id`       | то же + юр. поля; витрина позиций (`ShopItem`) |
| RecordLabel | `owner_user_id`      | то же + юр. поля; таблица `record_labels` |
| ProducerCenter | `owner_user_id`   | то же + юр. поля; таблица `producer_centers` |

Связи:

- `performer_admins` (`peformer_id`, `user_id`).
- `peformer_musician` (`peformer_id`, `musician_id`, `status`, `show_on_musician_profile`, `invited_by_user_id`, …).
- `teacher_city` (`teacher_id`, `city_id`).

Именование таблицы **`peformers`** сохраняем (исторический typo); модель **`Peformer`**.

## Чеклист реализации

- [x] **1.** Документ плана (этот файл).
- [x] **2.** Миграции: `2026_04_08_150000_music_ecosystem_public_profiles_and_performers` — публичные поля, владельцы, `schools`, `peformer_musician`, `peformer_admins`, `teacher_city`; снятие `unique` с `name` у `musicians`, `teachers`, `peformers`, `studios`, `rehearsals`.
- [x] **3.** Enums: `PerformerKind`, `PerformerMembershipStatus`, `LegalEntityType`.
- [x] **4.** Модели + связи `User`; `School`, pivot `PeformerMusician`; `Rehersal::$table = 'rehearsals'`.
- [x] **5.** Публичные маршруты (`/musicians/{slug}`, … `/schools/{slug}`, `/shops/{slug}`, `/labels/{slug}`, `/producer-centers/{slug}`), `PublicMusicProfileController`, заглушка «скрыто», минимальные view.
- [x] **5b.** Livewire `*Create`: убрана валидация `unique` по `name` (в БД уникальность снята).
- [x] **6.** Политики: `MusicianPolicy`, `TeacherPolicy`, `PeformerPolicy` (владелец и `peformer_admins`; удаление — только владелец), `StudioPolicy`, `RehersalPolicy`, `SchoolPolicy`, `ShopPolicy`, `RecordLabelPolicy`, `ProducerCenterPolicy`, `ShopOrderPolicy` (заказы); регистрация в `AppServiceProvider`.
- [x] **7.** Livewire-кабинет: музыкант, учитель; **исполнители**; **студии / репточки / школы / лейблы / продюсерские центры / магазины** (списки владельца + формы venue где применимо); блоки публичной страницы; на публичной странице исполнителя — состав при статусе `accepted`; реквизиты на площадках в `simple-entity`.
- [x] **8.** Приглашения в состав + уведомления (внутренние): `PerformerMembershipService`, `PerformerLineupInvitationNotification` (канал `database`), Livewire `PerformerLineupPanel` на редактировании исполнителя, блок на странице музыканта (входящие / принятые / выход / «показывать на странице»), блок `performers` в каталоге и на публичной странице музыканта при `accepted` + `show_on_musician_profile`.
- [x] **9.** Поиск музыкантов / групп / площадок: страница `music/discover` (авторизованные), публичный каталог `GET /discover` (гости и SEO), `MusicPublicSearchService` — только сущности с `public_page_enabled` и непустым `slug`; типы: музыкант, учитель, исполнитель, студия, репточка, школа, лейбл (`record_label`), продюсерский центр (`producer_center`), магазин; фильтр по типу; поля `name`, `description` (+ `bio` у музыкантов).
- [x] **9b.** Миграции магазина и смежных сущностей (`shops`, корзина, заказы и т.д. — см. репозиторий); кабинет `music/shops`, витрина на публичной странице магазина.
- [x] **9c.** Миграция `2026_04_09_210000_music_record_labels_and_producer_centers`: лейблы и продюсерские центры; кабинет `music/labels`, `music/producer-centers`; политики `RecordLabelPolicy`, `ProducerCenterPolicy`.
- [x] **10.** Адреса в UI (morph `Address`): панель `AddressBookPanel` в кабинете (музыкант, учитель, исполнитель, студия / репточка / школа, лейбл, продюсерский центр, магазин), снят ошибочный unique `(addressable_id, addressable_type, is_primary)`, блок `addresses` в layout-каталогах и на публичных страницах для `is_active` + `is_public`.
- [x] **11.** Расширение уведомлений: канал `mail` у `PerformerLineupInvitationNotification` (наряду с `database`), письмо со ссылкой на `music/musician`; настройка `users.notification_preferences.music_lineup_email` (по умолчанию включена), переключатель на странице «Настройки уведомлений» мессенджера.

## Порядок работ (следующие шаги)

Чеклист п.1–11 выполнен. Дальнейшее развитие (уведомления в UI, публичный каталог, админка, интеграции, модерация и др.) описано в **[music-ecosystem-roadmap.md](./music-ecosystem-roadmap.md)**.

**Дополнения в коде (2026-04-10), зафиксированы в roadmap:**

- Витрина магазина: категории из **`Good` → `Category`**, фильтр `?category=`; позиция **`shop_items.good_id` обязателен** (миграция + валидация + импорт).
- Заказы: суммы и **комиссия платформы** (`config/shop.php`, опционально `shops.platform_fee_rate`); статусы **`ShopPaymentStatus` / `ShopPaymentMethod`** без интеграции PSP — ручная отметка оплаты в MoonShine.
- Мессенджер: тип чата **`notice`** (лента сервисных сообщений), дубли оформления заказа и фиксации оплаты — `ShopOrderMessengerNotifier`, `MessengerService::getOrCreateNoticeFeed` / `postSystemMessage`.
