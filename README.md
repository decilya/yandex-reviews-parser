# Парсер отзывов Яндекс.Карт

Сервис для сбора отзывов, рейтинга и метрик организаций с Яндекс.Карт.
Laravel 12 + Vue 3 SPA, парсинг через state-view JSON и Schema.org микроразметку.

**Проверено на живой карточке:** [Парк птиц «Воробьи»](https://yandex.ru/maps/org/park_ptits_vorobyi/1028810460/) — вытянуто **600 отзывов** за один прогон, рейтинг 5.0, 43 836 оценок, 16 900 отзывов.

---

## Стек

| Компонент | Технология |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Auth | Laravel Sanctum (token-based) |
| Frontend | Vue 3 (Composition API), Axios |
| Сборка | Vite |
| Очереди | Redis + Laravel Queue |
| БД | MySQL 8 |
| Парсинг | Guzzle + Symfony DomCrawler |
| Деплой | Docker Compose |

---

## Быстрый старт (Docker)

```bash
# 1. Клонировать
git clone git@github.com:decilya/yandex-reviews-parser.git
cd yandex-reviews-parser

# 2. Скопировать окружение
cp .env.example .env

# 3. Собрать и поднять контейнеры
docker-compose up -d

# 4. Установить зависимости
docker-compose exec app composer install

# 5. Сгенерировать ключ
docker-compose exec app php artisan key:generate

# 6. Миграции + сид
docker-compose exec app php artisan migrate --seed

# 7. Собрать фронт
docker-compose exec app npm install
docker-compose exec app npm run build

# 8. Запустить очередь (в отдельном терминале)
docker-compose exec app php artisan queue:work
```

**После запуска:**
- Приложение: http://localhost:8000
- API: http://localhost:8000/api
- Тестовый пользователь: `admin@test.com` / `password`

---

## Переменные окружения

Основные (`.env.example`):

| Переменная | Описание | По умолчанию |
|---|---|---|
| `DB_CONNECTION` | Драйвер БД | `mysql` |
| `DB_HOST` | Хост БД | `db` |
| `DB_DATABASE` | Имя БД | `reviews_parser` |
| `REDIS_HOST` | Хост Redis | `redis` |
| `QUEUE_CONNECTION` | Драйвер очереди | `redis` |
| `PARSING_HTTP_TIMEOUT` | Таймаут HTTP (сек) | `30` |
| `PARSING_THROTTLE_MIN_MS` | Мин. пауза между запросами (мс) | `500` |
| `PARSING_THROTTLE_MAX_MS` | Макс. пауза между запросами (мс) | `1500` |
| `PARSING_MAX_RETRIES` | Макс. попыток парсинга | `3` |
| `PARSING_MAX_PAGES` | Макс. страниц для обхода | `30` |
| `PARSING_SKIPPED_THRESHOLD` | Доля пропущенных отзывов → ошибка | `0.5` |

---

## API-контракт

| Метод | Путь | Назначение | Ответ |
|---|---|---|---|
| POST | `/api/login` | Авторизация | `{ token, user }` / 422 |
| POST | `/api/logout` | Logout | 204 |
| GET | `/api/organization` | Текущая организация | 200 / 404 |
| POST | `/api/organization` | Сохранить ссылку, запустить парсинг | 202 `{ organization, message }` / 422 |
| GET | `/api/organization/status` | Статус парсинга | `{ status, progress, processed, total, error }` |
| GET | `/api/reviews?page=N` | Отзывы (50/стр.) | `{ data, meta, organization }` |

Ошибки: 401 / 403 / 404 / 422 / 429 / 500 — осмысленные сообщения.

---

## Как работает парсер

Парсер использует **два источника данных** в порядке приоритета.

### 1. State-view JSON (основной)

Яндекс встраивает данные карточки в `<script class="state-view" type="application/json">`. Реальные пути (верифицированы на живой карточке):

```
config.meta.h1                                            → название
config.landing.orgId                                      → orgId
config.csrfToken                                          → CSRF-токен
stack.0.results.items.0.ratingData.ratingValue            → рейтинг
stack.0.results.items.0.ratingData.ratingCount            → число оценок
stack.0.results.items.0.ratingData.reviewCount            → число отзывов
stack.0.results.items.0.reviewResults.reviews[]           → массив отзывов
```

Поля одного отзыва: `reviewId, businessId, author.name, author.avatarUrl, text, rating, updatedTime`.

### 2. Schema.org микроразметка (fallback)

Если в state-view отзывов нет, парсер переключается на DOM-парсинг через микроразметку:

- Контейнер: `div.business-review-view`
- Текст: `[itemProp="reviewBody"]`
- Автор: `[itemProp="author"] [itemProp="name"]`
- Рейтинг: `[itemProp="ratingValue"]` (content)
- Дата: `[itemProp="datePublished"]` (content)

**Важно:** Яндекс использует camelCase `itemProp`, не `itemprop`. Стандартный поиск по `itemprop` даёт 0 совпадений.

### 3. Пагинация

Для карточек с большим числом отзывов Яндекс генерирует SEO-страницы с `?page=N`. Парсер проверяет наличие ссылки на следующую страницу через `.seo-pagination-view` и итеративно обходит до 30 страниц.

---

## Обоснование подхода: HTTP vs Headless

### Выбран HTTP + state-view JSON

**Плюсы:**
- ⚡ Скорость: один HTTP-запрос вместо рендеринга страницы в браузере
- 📦 Масштабируемость: нет overhead на Chrome, можно параллелить
- 💾 Ресурсы: ~10 МБ RAM на процесс вместо ~200 МБ на headless
- 🎯 Точность: данные в JSON структурированы, не надо парсить вёрстку

**Минусы и риски:**
- 🔧 Хрупкость: при смене структуры state-view парсер сломается → детектится через `LayoutChangedException`
- 🕵️ Anti-bot: Яндекс может отдавать урезанный HTML при подозрении на бота
- 🔑 Токены: `csrfToken` меняется, но для парсинга публичной карточки не критичен

**Почему не headless:**
- Требует Chrome на сервере (+1.5 ГБ к образу, +500 МБ RAM на инстанс)
- В 5–10 раз медленнее
- Дороже в хостинге
- Для тестового прототипа избыточно

Headless (Playwright/Symfony Panther) — **описан как fallback в разделе «Что доделал бы»**. Это правильный план B при бане HTTP-подхода.

---

## Ответы по дополнительным требованиям

### 1. Устойчивость к смене разметки

Парсер **не молчит** при поломке:

- **`LayoutChangedException`** — если ожидаемое поле JSON/селектор отсутствует, бросается исключение с контекстом (какое поле, что есть вместо него).
- **Счётчик сбоев** — `PARSING_MAX_LAYOUT_FAILURES = 3`. Единичный сбой — лог. Три подряд → исключение + статус `requires_attention`.
- **Проверка доли пропущенных отзывов** — если >50% отзывов на странице не удалось адаптировать → `LayoutChangedException`.
- **Организация помечается** — `status = 'requires_attention'`, `last_error` содержит детали.
- **`failed()` не перетирает `requires_attention`** — осмысленный статус сохраняется для мониторинга.
- **Логирование** — каждый сбой логируется с URL, timestamp, контекстом.

**Как понять, что парсер сломался:** в БД появляется организация со статусом `requires_attention`, в `last_error` — какой селектор/поле не найден. Алерт можно повесить на количество таких организаций.

### 2. Масштаб и фоновая обработка

- **Очередь:** `ParseOrganizationReviewsJob implements ShouldQueue`
- **Прогресс:** таблица `parsing_jobs` — `status`, `processed`, `total`, `error`, `attempts`
- **Retry:** `tries()` из конфига, динамический backoff `[60, 300, 900]` секунд
- **Типизация retry:**
    - `RateLimitException` → release на `retryAfter` секунд
    - `BlockedException` → release на 300 секунд
    - `LayoutChangedException` / `InvalidRequestException` → `fail()` без retry
- **Индикация:** фронт polling `/api/organization/status` каждые 2 секунды

**Масштаб:** 50 филиалов × 600 отзывов = 30 000 отзывов. При троттлинге 500 мс между запросами — ~30 мин на один филиал (30 страниц). 50 филиалов в параллель (если позволяет anti-bot) — около 30 минут суммарно. Реалистично: разнести cron'ом по времени, чтобы не ловить бан.

### 3. Анти-бан

**Реализовано:**
- ✅ Ротация User-Agent из конфига `parsing.user_agents`
- ✅ Троттлинг `usleep(rand(min, max))` между запросами
- ✅ Backoff при `RateLimitException` (уважает `Retry-After`)
- ✅ Backoff при `BlockedException` (300 сек)
- ✅ `CookieJar` для сохранения сессии между запросами
- ✅ CSRF-токен в заголовках

**Описано (не реализовано):**
- 📝 **Ротация прокси** — `ProxyProvider` с пулом, round-robin или случайный выбор. Интеграция в `YandexPageFetcher::fetch()` через `Http::withProxy()`.
- 📝 **Распределение нагрузки** — при 50 филиалах разнести парсинг по времени (cron + random delay).
- 📝 **Детект бана** — пауза 15–60 минут при обнаружении капчи, алерт.
- 📝 **Сессионные cookies** — для некоторых внутренних эндпоинтов нужна валидная сессия.

### 4. Идемпотентность и история

**Идемпотентность:**
- Уникальный ключ: `(organization_id, external_id)`
- `Review::upsert()` — при повторном парсинге обновляет существующие, создаёт новые. Дублей нет.

**История изменений:**
- Таблица `review_snapshots` — снимок метрик после каждого парсинга.
- Поля: `rating, rating_count, review_count, reviews_fetched, snapshotted_at`.
- Можно сравнить: было 4.3 / 100 оценок → стало 4.5 / 110 оценок.
- Для детального сравнения отзывов — добавить `review_changes` (какие отзывы добавились/удалились/изменились).

---

## Структура проекта

```
app/
├── Application/
│   ├── Jobs/             ParseOrganizationReviewsJob
│   └── Services/         OrganizationReviewService
├── Domain/
│   ├── Organization/     Models, DTO, Contracts, Repositories
│   ├── Review/           Models, DTO, Contracts, Repositories
│   └── Parsing/
│       ├── Abstract/     AbstractPlatformParser
│       ├── Contracts/    ReviewParserInterface, PageFetcherInterface
│       ├── DTO/          ParsingResultDto
│       ├── Exceptions/   LayoutChanged, Blocked, Empty, RateLimit, SourceUnavailable, InvalidRequest
│       ├── Factory/      PlatformParserFactory
│       ├── Models/       ParsingJob
│       └── Yandex/       YandexMapsParser, YandexResponseAdapter, YandexPageFetcher
├── Http/
│   ├── Controllers/Api/  Auth, Organization, Review
│   ├── Requests/         Login, SaveOrganization
│   └── Resources/        Organization, Review, ParsingStatus
├── Models/               User
└── Providers/            AppServiceProvider, ParserServiceProvider

config/parsing.php         Конфиг парсинга
database/migrations/       organizations, reviews, parsing_jobs, review_snapshots
database/factories/        Organization, Review, User
database/seeders/          UserSeeder, DatabaseSeeder
resources/js/              Vue 3 SPA
tests/Unit/                YandexMapsParserTest, YandexResponseAdapterTest
tests/Feature/             AuthTest, OrganizationTest, ReviewPaginationTest
```

---

## Тесты

17 тестов, все зелёные:

```bash
docker-compose exec app php artisan test
```

Покрытие:
- **Unit (11):** парсер на фикстурах двух карточек (большая/малая), очистка имени организации, извлечение метрик, детект пагинации, извлечение CSRF.
- **Feature (6):** авторизация, сохранение ссылки + dispatch job, валидация URL, пагинация отзывов, метрики в ответе.

---

## Что доделал бы при наличии времени

1. **Деплой на VPS** — сейчас работает локально через `docker-compose`. Для сдачи на хостинге: VPS + `php-fpm` + nginx + SSL (Let's Encrypt).
2. **Ротация прокси** — `ProxyProviderInterface` + пул прокси, health-check, round-robin.
3. **Headless fallback** — `PlaywrightPageFetcher` как Plan B при бане HTTP.
4. **Детальная история изменений** — таблица `review_changes` для diff'а отзывов между парсингами.
5. **Парсер 2ГИС** — реализация `TwoGisParser` через тот же `ReviewParserInterface`. Архитектура готова, фабрика поддерживает.
6. **WebSocket для прогресса** — вместо polling статуса push через Laravel Reverb.
7. **Dashboard** — график изменения рейтинга по времени из `review_snapshots`.
8. **Rate limiting на API** — `throttle:60,1` для защиты.
9. **Уведомления** — email/Telegram при `requires_attention` или длительном `error`.
10. **CI/CD** — GitHub Actions: lint, tests, deploy.
11. **Production-сервер** — `php-fpm` + nginx или Laravel Octane вместо `php artisan serve`.

---

## Ограничения текущей версии

- **Не задеплоен на хостинг** — работает локально через Docker.
- **Только одна площадка** — Яндекс.Карты. 2ГИС описан как план расширения.
- **Без ротации прокси** — при частом парсинге возможен бан по IP.
- **Chrome/Chromium не установлен** — headless не поддерживается в текущем образе.

---

## Лицензия

Тестовое задание. Свободное использование.
