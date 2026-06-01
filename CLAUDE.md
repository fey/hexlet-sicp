# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Hexlet SICP — трекер изучения книги SICP. Пользователи читают главы (иерархическое дерево), решают упражнения на Scheme/Racket и отслеживают прогресс с лидербордами. Стек: **Laravel 13 (PHP 8.3+), Inertia.js + React 19 + Vite, Blade, SQLite (local) / PostgreSQL (prod)**.

## Commands

Всё запускается через Makefile. Локально (SQLite + локальный PHP):

- `make setup` — полная инициализация (env, sqlite, install, key, миграции+сиды, ide-helper, сборка фронта)
- `make start` — поднять сервер (heroku local -f Procfile.dev) на http://127.0.0.1:8000
- `make start-app` — только PHP-сервер; `make start-frontend` — только Vite dev
- `make test` — тесты (`php artisan test`)
- `make lint` — lint JS + PHP; `make lint-fix` — автофикс (phpcbf + prettier для blade)
- `make db-prepare` — `migrate:fresh --force --seed`
- `make cache-clear` — сброс config/cache/view (нужно при `CSRF token mismatch`)

Тесты (phpunit, три testsuite — Unit / Feature / Exercises, по умолчанию Feature):

- Один файл: `vendor/bin/phpunit tests/Feature/Http/Controllers/HomeControllerTest.php`
- Один метод: `vendor/bin/phpunit --filter testIndex`
- Testsuite: `vendor/bin/phpunit --testsuite Unit` (или Feature / Exercises)
- Проверка teacher-решений: `make test-solutions` (composer exec phpunit -- --testsuite Exercises)

Линтеры: `make lint-php` (phpcs, PSR-12 + Slevomat), `make lint-js` (eslint flat config). Перед push хук `pre-push-hook` гоняет lint+analyse.

Docker: команды с префиксом `compose-*` в `make-compose.mk` (например `make compose-test`, `make compose-setup`). PostgreSQL локально: `make compose-start-database` + `make db-prepare`.

## Architecture

### Домен (app/Models)

- **Chapter** — главы SICP в дереве (`parent_id`, `path` вида "1.1.2"). Читаема только если лист (`getCanReadAttribute` — нет детей).
- **Exercise** — упражнения, привязаны к главе. Тесты и эталонное решение лежат в Blade-стабах: `resources/views/exercise/solution_stub/{path}.blade.php` и `{path}_solution.blade.php`; тесты извлекаются после маркера `;;; END`.
- **ExerciseMember / ChapterMember** — join-таблицы прогресса пользователя со **state machine** (`started → finished`, переход `finish()`). За завершённое упражнение начисляется 3 балла. Конфиг графов: `config/state-machine.php` (графы `chapter_member`, `completed_exercise`), пакеты iben12/laravel-statable + sebdesign/laravel-state-machine.
- **Solution** — версии решений (scope `versioned()` — последнее на пользователя+упражнение). **Comment** — полиморфные вложенные комментарии. **Activity** — аудит через spatie/laravel-activitylog.

### Проверка решений (ключевой флоу)

- `app/Services/SolutionChecker.php` исполняет `raco test` (Racket) в шелле, оборачивая код+тесты в sandbox-шаблон, временные файлы в `storage/solutions/`. **Требует установленного Racket** — локально `raco` может отсутствовать (тогда testsuite Exercises не пройдёт).
- `app/Services/ExerciseService.php` оркестрирует `check()` (валидация → лог активности → переход в finished + баллы) и `createSolution()`.
- API: `POST /api/exercises/{id}/check`, `POST /api/exercises/{id}/solutions`, `GET /api/exercises/{id}`.

### Слои

- Контроллеры `app/Http/Controllers` сгруппированы по неймспейсам: `Api/`, `Admin/`, `Auth/`, `Settings/`, `User/`, `My/`, `Rating/`.
- Сервисы `app/Services` (ExerciseService, SolutionChecker, ChapterProgressService, ActivityService, RatingCalculator).
- DTO через spatie/laravel-data (`app/DTO`), презентеры через hemp/presenter (`app/Presenters`), политики (`app/Policies`).
- Интеграции: Socialite (GitHub/Yandex OAuth), Sentry, knplabs/github-api, spatie/laravel-sitemap.

### Фронтенд (hybrid Blade + Inertia/React)

- Inertia-корень: `resources/views/app.blade.php` (`@inertia`). React-вход: `resources/js/app.jsx`; компоненты в `resources/js/components`, страницы в `resources/js/pages`, Redux в `resources/js/slices`. Алиас `@` → `resources/js`.
- Vite (`vite.config.js`) — несколько entry points (app.jsx, editor.js, hljs.js, custom.js, app.scss).
- Локализация двухслойная: бэкенд `resources/lang/{en,ru}` + mcamara/laravel-localization (маршруты префиксованы локалью `/{locale}/...` в `routes/web.php`); фронтенд i18next (`resources/js/i18n.js`, `resources/js/locales/{en,ru}.js`).

### Тесты
- Базовые классы: `tests/TestCase.php` (RefreshDatabase, WithFaker) и `tests/ControllerTestCase.php` (создаёт авторизованного User в setUp). Фабрики в `database/factories`. Тестовое окружение — SQLite `:memory:`.


## Conventions

- PHP: PSR-12 + Slevomat (phpcs.xml). Строгие сравнения `===`, обязательные null-coalesce, **запрет `++`/`--`**, trailing commas в массивах, без mixed type hints. Запускать `make lint-php` / `make lint-fix`.
- JS/React: ESLint flat config (eslint.config.js) — React 19 (без prop-types и импорта React в JSX), arrow-parens always, comma-dangle always-multiline. Blade форматируется prettier через @shufo/prettier-plugin-blade.
