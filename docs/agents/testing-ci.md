# Тесты в CI и Allure TestOps

Результаты PHPUnit уезжают в Allure TestOps (проект `hs`, projectId 100). Имена тест-кейсов задаёт код — ADR-0005.

## phpunit.xml

- `stopOnFailure` и `stopOnError` из `phpunit.xml` убраны: прогон идёт до конца и показывает все падения сразу. Возвращать их не надо — на них ломается заливка результатов в Allure TestOps, которой нужен полный launch, а не обрезанный на первой ошибке (ADR-0005).
- Любой прогон пишет результаты в `build/allure-results` (`/build` в `.gitignore`): адаптер `allure-framework/allure-phpunit` подключён в `phpunit.xml` через `<extensions><bootstrap>`, а **не** `<extension>` — второй вариант из PHPUnit 9 в 13 не работает. Параметр `config` намеренно не задан: без него файл конфигурации необязателен и действуют дефолты, а если параметр передать, указанный файл обязан существовать.

## Заливка из CI (`.github/workflows/ci.yml`)

- Заливка в TestOps идёт **только на push в main**: `allurectl watch` оборачивает `make ci` целиком и заливает даже при падении тестов, возвращая исходный код выхода. Отдельный шаг с `if: always()` для этого не нужен. Каталог результатов лежит в bind-маунте, поэтому `docker compose down -v` в конце `make ci` его не уносит.
- **`ALLURE_RESULTS` в воркфлоу обязателен, дефолта у allurectl нет.** Дублированием пути из адаптера это только кажется: дефолт адаптера говорит, куда PHPUnit пишет результаты внутри контейнера, а `ALLURE_RESULTS` — откуда allurectl читает их на раннере. Без него `watch` создаёт launch, заливает в него **ноль** файлов и возвращает успех, то есть зелёная сборка ничего не говорит о заливке. Именно так и произошло в первом прогоне после #2024. Ловит это шаг `Check that test results were produced` — не удаляй его.

- Шаг `Run CI` экспортирует `UID` раннера: `docker-compose.ci.yml` передаёт его build-аргументом, и контейнер работает под владельцем checkout'а. Без этого git внутри контейнера отказывается работать с репозиторием («dubious ownership»), и падает `make types-check`.

## Как результаты выглядят в TestOps

- Тесты с `#[DataProvider]` попадают в TestOps **одним** тест-кейсом: у всех наборов данных один `fullName` и один `testCaseId`, различаются параметром `Data set`.

## MCP `allure-testops`

Токен Allure в git не лежит: заголовок собирается из `${ALLURE_TESTOPS_TOKEN}`. Чтобы сервер заработал у себя:

1. Создать личный токен в TestOps: аватар → *Profile* → *API tokens*.
2. Положить его в `env.ALLURE_TESTOPS_TOKEN` в `.claude/settings.local.json` (файл вне git) или в переменную окружения.
3. Разрешить сервер при первом запуске — либо добавить `allure-testops` в `enabledMcpjsonServers` там же.

Переменная не задана — конфиг всё равно загрузится, `${ALLURE_TESTOPS_TOKEN}` уйдёт в заголовок как есть, и сервер молча не будет работать. **`claude mcp list` это не поймает: он печатает `✔ Connected` и без токена.** `claude mcp get` тоже бесполезен — показывает конфиг до подстановки. Проверять только вызовом инструмента, например `testops_get_project` (в дочерней сессии: `claude -p --permission-mode default --allowedTools "mcp__allure-testops__testops_get_project"`).
