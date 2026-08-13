# Практики из hexlet-college-spa

Разбор проекта `~/projects/spa` (hexlet-college-spa, Laravel 11 + Inertia 2 + React 19 + TypeScript + Bootstrap) — что там надстроено **поверх** стандартного фреймворка и что из этого стоит перенести в hexlet-sicp.

Проект не форк и не библиотека — это отдельное приложение, из которого имеет смысл забирать конвенции, а не код. Многое напрямую списано с Rails: скоуп переводов от контроллера, `form_for`-подобные поля, `Index/Edit/_form` в папке ресурса, флеш по ключу, strong parameters.

Ссылки на файлы — относительно корня `~/projects/spa`.

---

## 1. Конвенция вместо конфигурации в контроллерах

### 1.1. `render()` выводит имя страницы из имени экшена

`app/Http/Controllers/Controller.php`

```php
protected function render(array $props = [], ?string $view = null)
```

`College\Admin\InvoiceTypeController@index` → компонент `College/Admin/InvoiceType/Index`. Явный `$view` остаётся как аварийный выход. Контроллер не знает про пути к JSX вообще:

```php
public function index(Request $request, College $college): Response
{
    return $this->render(['invoiceTypes' => $college->invoiceTypes]);
}
```

Побочно `render()` кладёт shared-пропы и пропы страницы в Debugbar — видно, что реально уехало на фронт.

**Ценность:** высокая. Убирает целый класс ошибок «переименовали контроллер, забыли переименовать строку».

### 1.2. Скоуп страницы вычисляется из маршрута и едет на фронт

`app/Http/Helpers/TranslationHelper.php` превращает action-name в точечный ключ:
`App\Http\Controllers\College\Admin\InvoiceTypeController@index` → `college.admin.invoice_type.index`.

Этот `scope` шарится Inertia-мидлваром (`app/Http/Middleware/InertiaRequestsMiddleware.php`), а на фронте `useViewTranslation(scope)` даёт относительные ключи — точь-в-точь рельсовый `t('.title')`:

```tsx
const { props: { scope } } = usePage<CollegeSharedProps>()
const { t: tView } = useViewTranslation(scope)
...
<AdminLayout header={tView('.title', { name: college.name })}>
```

Соответственно `lang/ru/views.json` повторяет дерево контроллеров: `views.college.admin.invoice_type.index.title`.

**Ценность:** очень высокая. Ключ перевода перестаёт быть строкой, которую надо придумывать и синхронизировать; он выводится из места, где находится код.

### 1.3. Флеш — только тип, текст берётся по скоупу

```php
$this->flash('success');   // всё, ни строки текста в контроллере
```

`flash()` собирает `['type' => 'success', 'key' => 'college.admin.invoice_type.store.success', 'params' => []]` и кладёт в сессию. Мидлвар прокидывает это в shared-проп `flash`, компонент `resources/js/Components/Flash.tsx` переводит на фронте по неймспейсу `flash`. Плюс: если флеша нет, но в сессии есть `errors`, мидлвар сам подставляет `danger`-сообщение.

**Ценность:** высокая. Тексты уходят из PHP целиком, а ключ выводится из экшена.

Оговорка: `Flash.tsx` рендерит текст через `dangerouslySetInnerHTML` — ради ссылок внутри сообщений. Переносить не стоит, или стоит с санитайзом.

---

## 2. Валидация и формы: strong parameters по-рельсовому

### 2.1. Правила живут в модели и уже отпрефиксованы именем сущности

`app/Models/College/InvoiceType.php`:

```php
public static function validationRules(): array
{
    $rules = [
        'instruction' => ['nullable'],
        'college_invoice_template_id' => ['nullable'],
        'type' => ['required'],
    ];

    return Arr::prependKeysWith($rules, 'college_invoice_type.');
}
```

`app/Models/BaseModel.php` обобщает это через `EntityNameEnum`: `prefixedValidationRules()` берёт имя сущности из энума, так что префикс не дублируется руками.

Контроллер работает с ними напрямую, FormRequest в CRUD не используется:

```php
$validated = $request->validate(InvoiceType::validationRules());
$college->invoiceTypes()->create($validated['college_invoice_type']);
```

А для частичного апдейта — `Arr::only()` по нужным полям, а не отдельный класс правил.

Форма на фронте отправляет данные ровно в этой же форме — `{ college_invoice_type: { type, instruction, ... } }`. Это рельсовые `params[:college_invoice_type]`.

### 2.2. Правила уезжают на фронт и рисуют звёздочки

Контроллер отдаёт `'validationRules' => InvoiceType::validationRules()`, а поля формы сами их читают:

```tsx
const { props: { validationRules } } = usePage<{ validationRules: ValidationRules }>()
const starred = (_.get(validationRules, name, []) as string[]).includes('required')
```

`app/Lib/ViewValidationRules.php` нужен, чтобы правила пережили JSON: он прогоняет их через `Validator::make([], $rules)->getRules()` и разворачивает объекты-правила в строки через `__toString()`.

**Ценность:** высокая — обязательность поля перестаёт быть продублированной в разметке. Но это работает только потому, что правила и имена полей заданы в одной системе координат (`entity.field`).

### 2.3. `EntityNameEnum` — сквозной идентификатор сущности

`app/Enums/EntityNameEnum.php` + `BaseModel::entityName()`. Одна и та же строка (`college_invoice_type`) работает как:

- префикс полей формы,
- ключ в `lang/ru/models.json`,
- ключ TS-типа (`ModelName`),
- ключ правил валидации.

Это цемент, на котором держатся автолейблы, автозвёздочки и типизация. Без него остальное рассыпается.

---

## 3. Переводы как схема данных (самое интересное)

### 3.1. Неймспейсы по назначению

`lang/ru/`:

| файл | что внутри |
|---|---|
| `models.json` | имена моделей и подписи атрибутов, плюс общий раздел `base` |
| `views.json` | тексты страниц по скоупу контроллера |
| `flash.json` | флеш-сообщения по скоупу экшена |
| `fsm.json` | названия состояний и переходов по модели/полю |
| `layouts.json` | лейауты и меню |
| `breadcrumbs.json` | хлебные крошки |
| `custom.json` | энумы, `helpers.actions.*`, общие слова |

PHP-файлы (`validation.php`, `auth.php`, `data.php`…) остаются под бэкенд, JSON — общие.

### 3.2. Один и тот же JSON импортируется во фронт

`resources/js/i18resources.ts`:

```ts
import { models } from '../../lang/ru/models.json'
import { views } from '../../lang/ru/views.json'
...
```

Никакого экспорта переводов, никакой генерации — Vite тянет те же файлы. PHP остаётся единственным источником (у нас это зафиксировано в ADR 0003, и здесь тот же вывод, но реализация проще нашей).

### 3.3. Из словаря выводятся типы TypeScript

`resources/js/types/entities.d.ts` — ключевой трюк всего проекта:

```ts
export type Model = typeof resources.ru.models
export type ModelName = keyof Model & App.Enums.EntityNameEnum
export type ModelsProperty<T extends ModelName> = keyof Model[T]['attributes'] & string

export type FSM = typeof resources.ru.fsm
export type FSMFieldName<M extends FSMModelName> = keyof FSM[M] & string

export type EnumName = keyof typeof resources.ru.custom.enums
```

Дальше `scope` в shared-пропах типизирован как `Paths<typeof resources.ru.views>` (`resources/js/types/inertia.d.ts`), а `i18next` знает о ресурсах через `declare module 'i18next'` (`types/i18next.d.ts`).

Итог: `tModel('college_invoice_type', 'type')` проверяется компилятором, опечатка в ключе перевода — ошибка сборки, а не пустая строка в UI.

**Ценность:** очень высокая, и это ровно то, чего у нас нет. Стоит рассмотреть даже отдельно от остального.

### 3.4. Хелперы переводов

`resources/js/lib/i18next.ts`:

- `tModel(model, attr?)` — подпись атрибута с фоллбеком на `models.base.attributes` (рельсовый `human_attribute_name`);
- `tFSM(model, field, 'states'|'events', name)` — название состояния;
- `useViewTranslation(scope)` — относительные ключи `t('.title')`;
- `getPluralForm(n)` — русские формы множественного числа руками (i18next-плюрализация не использована).

### 3.5. Опции селектов берутся из переводов

`resources/js/lib/options.ts`:

```ts
export function getEnumOptions(enumName: EnumName, include: string[] = []): XselectOption[] {
  const items = i18n.t(`enums.${enumName}`, { returnObjects: true })
  ...sort по локали
}
export function getStateOptions(modelName, stateFieldName, key?, include?) { ... }
```

Список значений энума не гоняется с бэка — он уже есть в словаре. `include` позволяет сузить набор.

**Ценность:** высокая для форм с энумами и состояниями.

---

## 4. FSM и состояния

`app/Lib/StateMachineSerializer.php` отдаёт на фронт не только текущее состояние, но и граф:

```php
['state' => ..., 'canTransitionTo' => [...], 'statesTo' => [...]]
```

Фронт рисует доступные переходы, ничего не зная о правилах. Типы — `CollegeAdmissionStateFSM` и родня в `entities.d.ts`, тексты — в `fsm.json`.

У нас FSM другая (iben12/laravel-statable + sebdesign), но приём переносится один в один: **не отдавать состояние строкой, отдавать состояние + список доступных переходов**.

---

## 5. Мультитенантность и слоёные shared-пропы

Разделение по слоям — тоже полезная идея независимо от тенантов (`bootstrap/app.php`):

```php
Route::middleware([
    'web',
    RequestDataMiddleware::class,       // резолв тенанта
    AppInertiaRequestsMiddleware::class,// базовые shared-пропы
    WebDataMiddleware::class,           // пропы пользовательской части
])->domain($subdomain)->as('college.')->group(base_path('routes/college.php'));
```

- `RequestDataMiddleware` — резолвит колледж по поддомену, кладёт в `$request->attributes` **и** в `Illuminate\Support\Facades\Context`;
- `BaseModel::scopeForCurrentCollege()` читает тенанта из `Context` — без глобальных переменных и без прокидывания через аргументы;
- `WebDataMiddleware` / `ManagementDataMiddleware` — по одному Inertia-мидлвару на группу маршрутов, каждый шарит только своё (счётчики непрочитанного, права, тенант). Один пухнущий `HandleInertiaRequests` не появляется.
- `Controller::sharePolicy($model, $ability)` — точечно доливает права в проп `can` прямо из экшена.

`SingletonControllerMiddleware` — параметризованный мидлвар: если у тенанта нет нужной интеграции, ставит флеш и редиректит на её создание. Проверка не размазана по контроллерам.

**Ценность для нас:** тенантов нет, но приёмы «shared-пропы по группам маршрутов» и «`Context` вместо глобалей» применимы.

---

## 6. Фильтры, сортировка, пагинация

`app/Data/Filters/College/Admin/Cohort/IndexFilter.php` — один DTO держит обе стороны фильтра:

```php
#[TypeScript]
class IndexFilter extends BaseFilter
{
    public function __construct(
        public readonly ?string $price_per_semester,
        ...
    ) {}

    public static function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('price_per_semester'),
            AllowedFilter::callback('college_academic_start_year_id',
                fn (Builder $q, int $v) => $q->whereRelation('academicStartYear', 'id', $v)),
        ];
    }
}
```

Он же уезжает в TS через spatie/typescript-transformer, так что форма фильтра на фронте типизирована тем же классом. В контроллере:

```php
$cohortsPage = QueryBuilder::for($query)
    ->allowedFilters(IndexFilter::allowedFilters())
    ->paginate()->withQueryString();
```

На фронте всё живёт в query string: `FilterWrapper` делает `form.get(url)`, `Sort` перещёлкивает `?sort=field` → `?sort=-field` → пусто, `Paging` использует готовые `next_page_url` из пагинатора. `filter` и `sort` шарятся глобально мидлваром.

Один нюанс: `Components/Sort.tsx` собирает URL на фронте через ziggy — у нас так нельзя (ADR 0002, локаль-префикс). В самом файле, кстати, висит комментарий автора: «работу с sort лучше делать на беке».

---

## 7. Фронтенд: слой поверх Inertia

### 7.1. `Xform` — поля, которые знают про модель

`resources/js/Components/Xform.tsx` — namespace-компонент:

```tsx
export default Object.assign(Xform, { Input, Select, AsyncSelect, Check, CollegeFile, File, MoneyInput })
```

Использование (`Pages/College/Admin/InvoiceType/Fields.tsx`):

```tsx
<Xform.Select name="college_invoice_type.type" options={invoiceTypesOptions} inertiaForm={form} />
<Xform.Input name="college_invoice_type.instruction" rows={15} as="textarea" inertiaForm={form} />
```

Из одного `name` поле само достаёт:

- **лейбл** — разбирает `name` на модель и атрибут, зовёт `tModel()`, при промахе падает на `models.base`;
- **ошибку** — из `form.errors` по тому же ключу;
- **обязательность** — из шаренного `validationRules`;
- **значение** — по пути через `lodash.get`.

Типизация пути — `Paths<T>` из `type-fest`, то есть `name` проверяется против типа формы.

Это и есть главный DX-выигрыш проекта: поле формы описывается одной строкой, а не пятью.

### 7.2. `setData` по пути

`resources/js/lib/inertia.ts` — `useForm().setData` из коробки не умеет вложенные пути:

```ts
export function setData<T>(form: InertiaFormProps<T>, name: Paths<T>, value: unknown): void {
  form.setData((data) => { _.set(data, name, value); return { ...data } })
}
```

Спред в конце — воркэраунд отсутствия ререндера на чекбоксах, помечен TODO.

### 7.3. Раскладка страниц ресурса

```
Pages/College/Admin/InvoiceType/
  Index.tsx    список
  Create.tsx   форма создания
  Edit.tsx     форма редактирования
  Fields.tsx   общий набор полей (рельсовый _form)
  Menu.tsx     табы ресурса: список / создать / редактировать / удалить
  Show.tsx     — где нужно
```

`Fields.tsx` гарантирует, что create и edit не разъедутся; `Menu.tsx` — единая навигация по ресурсу с подсветкой активного пункта через `router.current()`.

`SharedPages/` — отдельный каталог для страниц, переиспользуемых между неймспейсами (публичная и управленческая версии счёта).

### 7.4. `methods/` — презентеры сущностей на фронте

`resources/js/methods/college/invoiceTypeMethods.ts` и соседи:

```ts
const invoiceTypeMethods = {
  getTitle(invoiceType?: CollegeInvoiceType | null) { ... },
}
```

Форматирование сущности собрано в одном месте, а не расползается по компонентам. По сути — модельные методы/презентеры (у нас на бэке эту роль играет hemp/presenter, на фронте аналога нет).

`resources/js/lib/forms.ts` — типы форм плюс билдеры начальных данных (`getPassportBlock`, `getManagementInvoiceFormData`), чтобы `useForm` в Create и Edit заполнялся одинаково.

### 7.5. Хлебные крошки как хук

`resources/js/Components/Breadcrumbs.tsx` — `useBreadcrumbs()` возвращает объект готовых цепочек (`adminCollegesEdit(college)`), собранных из переиспользуемых сегментов. Страница пишет одну строку: `const segments = b.adminInvoiceTypes()`.

### 7.6. Типы генерируются, а не пишутся

- энумы, DTO, состояния — spatie/typescript-transformer → `resources/js/types/generated.d.ts`;
- модели — самописный `app/Lib/Transformers/LiftModelTransformer.php`: тянет публичные типизированные свойства моделей на `wendelladriel/laravel-lift` и печатает TS-интерфейс;
- маршруты — ziggy `--types-only` плюс обёртка `resources/js/lib/ziggy.ts` с `StrictRouteName`, которая вычищает `string` из юниона имён, так что опечатка в имени маршрута ловится компилятором.

`app/Lib/Transformers/FsmTransformer.php` — задел под генерацию типов состояний, пока `return null`.

Ziggy у нас отклонён (ADR 0002) и переносить его не надо, но идея «имена маршрутов — часть системы типов» стоит того, чтобы придумать нашу версию.

---

## 8. DX и инфраструктура

### 8.1. `make sync-code` — одна цель для всех генератов

```make
sync-code:
	php artisan cache:clear
	php artisan migrate
	php artisan ide-helper:generate
	php artisan lang:update
	php artisan ide-helper:models --write
	php artisan typescript:transform
	php artisan ziggy:generate --types-only
	make lint-fix
	php artisan storage:link --force
```

Всё производное пересобирается одной командой. Это важнее, чем кажется: как только генератов больше двух, без такой цели они начинают расходиться.

`make lint` тоже собран целиком: phpstan + php-cs-fixer + eslint + `npx tsc`. Хуки через `simple-git-hooks`: pre-commit → `lint-staged` в докере, pre-push → тесты.

### 8.2. Дебаг

- `Controller::render()` кладёт shared- и page-пропы в Debugbar;
- `app/Http/Middleware/DebugBarManagerMiddleware.php` включает Debugbar на не-локальных окружениях по списку email;
- `app/Lib/ModelDebugInfo.php` — доменный дамп (`dd()` с разложенными ценами, скидками, типом договора) для быстрой диагностики.

Первое и третье — хорошие идеи. Второе — хардкод почт в коде, так делать не надо.

### 8.3. Тесты

`tests/CollegeTestCase.php` — обёртка над `route()`, подставляющая тенанта в каждый вызов:

```php
protected function route(string $name, array $parameters = [], bool $absolute = true): string
{
    return route($name, ['college' => $this->college->subdomain, ...$parameters], $absolute);
}
```

Наш аналог — локаль в префиксе; такой хелпер снял бы кучу шума.

`tests/Helpers.php` — детерминированные id из сидов:

```php
public static function getId(string $name): int  { return crc32($name); }
public static function fixSequenceId(string $tableName): void { /* alter sequence ... restart with */ }
```

То есть базовый датасет — сиды, а не фабрики, и на конкретную сид-запись ссылаются по смысловому имени: `Admission::findOrFail(Helpers::getId('user4_admission_waiting_for_payment'))`. `fixSequenceId` чинит postgres-последовательности после вставки записей с явными id — нам это тоже актуально после перехода на PostgreSQL.

`tests/TestCase.php`:

- `RefreshDatabaseState::$migrated = true` локально (в CI — честная миграция) — ускорение прогона, но с пометкой FIXME от автора;
- `withoutExceptionHandling()` в `setUp` — как у нас;
- `assertFieldsEqual(array $fields, Model $model)` — сравнение пачки полей с моделью, с разворачиванием энумов.

Тесты пишутся на атрибутах PHPUnit (`#[Test]`), проверяют `assertSessionHasNoErrors()` + `assertRedirect()` + состояние модели после перехода — то есть флоу целиком, а не только код ответа.

### 8.4. Деньги

Целые копейки в БД + `app/Casts/MoneyCast.php` на brick/money, форматирование на фронте через `Intl.NumberFormat` (`resources/js/lib/numbers.ts`). Строк с рублями в коде нет.

### 8.5. Отложенные пропы

`Inertia::defer(fn () => ..., 'group')` в `app/Http/Controllers/College/Guest/AdmissionController.php` — тяжёлые справочники грузятся не на первом рендере, а группами по шагам визарда. Прямо применимо к нашим спискам глав и упражнений.

---

## 9. Что переносить не надо

- **`Controller::t()`** — достаёт перевод как `Arr::get(__(''), $fullKey, $fullKey)`, то есть тянет весь словарь и лезет в него массивом. Работает, но это хак.
- **`RequestHelper`** — вторая схема именования полей (`prefix__field` вместо вложенности) для multipart-форм. Наполовину заброшена: в `InertiaRequestsMiddleware::resolveValidationErrors` соответствующий код закомментирован, в самом хелпере висит вопрос автора «стоит ли так делать». Не тащить.
- **Ziggy** — у нас отклонён осознанно (ADR 0002), и половина URL-хелперов проекта на нём завязана.
- **`Sort.tsx`** — собирает URL на фронте, у нас это ломает локаль-префикс.
- **`dangerouslySetInnerHTML` во флеше** — либо санитайз, либо не переносить.
- **Хардкод email в `DebugBarManagerMiddleware`.**
- **Закомментированный код** — его в проекте много (`grid.ts` состоит из него целиком, `ssr.tsx` закомментирован полностью, `FsmTransformer` возвращает `null`). Это следы незавершённых заходов, а не практика.

---

## 10. Что взять в hexlet-sicp, по убыванию отдачи

1. **Скоуп переводов от контроллера** (`TranslationHelper` + `useViewTranslation`) — относительные ключи `t('.title')` вместо ручных путей. Ложится на нашу hybrid-схему без конфликтов: Blade-страницы просто не пользуются скоупом.
2. **Типы TS из словаря переводов** (`ModelName`, `ModelsProperty`, `EnumName`, типизированный `scope`) — компилятор начинает ловить опечатки в ключах. Не требует ни ziggy, ни Inertia.
3. **Флеш по ключу, а не текстом** — вместе с п.1 выносит все тексты из PHP-кода.
4. **`Controller::render()` по конвенции** — имя Inertia-страницы из имени экшена.
5. **Правила валидации в модели + отдача на фронт** — автоматические звёздочки обязательных полей; требует единого именования полей формы (`entity.field`).
6. **Раскладка `Index/Create/Edit/Fields/Menu`** — сейчас у нас одна Inertia-страница (`Settings/Profile/Index.jsx`), самое время задать конвенцию до того, как их станет двадцать.
7. **`Xform`-подобные поля с автолейблом** — крупнейший выигрыш по строкам кода, но требует п.2 и п.5. У нас Mantine, так что это своя реализация, а не копипаста.
8. **`make sync-code`** — одна цель для всех генератов.
9. **Хелпер `route()` в тестах** с подстановкой локали — по аналогии с их подстановкой тенанта.
10. **`Helpers::fixSequenceId`** — актуально после перехода на PostgreSQL (#121).
11. **FSM: отдавать `canTransitionTo` вместе с состоянием** — фронт перестаёт дублировать граф переходов.
12. **`Inertia::defer` для тяжёлых справочников.**

Пункты 1–4 самодостаточны, их можно делать по отдельности и в любом порядке. Пункты 5 и 7 связаны: без общего именования полей автолейблы не заводятся.
