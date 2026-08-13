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

То есть базовый датасет — сиды, а не фабрики, и на конкретную сид-запись ссылаются по смысловому имени: `Admission::findOrFail(Helpers::getId('user4_admission_waiting_for_payment'))`. `fixSequenceId` чинит postgres-последовательности после вставки записей с явными id.

Нам `fixSequenceId` не нужен: в `database/seeders/**` и `database/factories/**` нет ни одной записи с явным `id`, последовательностям разъезжаться не от чего.

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

## 10. Что из этого уже есть в hexlet-sicp

Прежде чем что-то предлагать — сверка с текущим состоянием репозитория и с [docs/frontend-migration.md](frontend-migration.md).

**Уже в коде:**

- `Controller::inertia()` (`app/Http/Controllers/Controller.php`) — прямой порт `render()` из spa, вместе с выводом имени страницы из имени экшена и отправкой shared/page-пропов в Debugbar. Забирать нечего, оно уже здесь.

**Уже решено в плане миграции, реализация не начата:**

- `scope` в shared-пропах + `useTView()` с относительными ключами `tView('.title')` — с важной поправкой: **скоуп задаётся явно**, а не выводится из имени контроллера, потому что дерево наших словарей не совпадает с деревом контроллеров (таблица расхождений — в плане миграции);
- `<Field name>`, выводящий label и error из одного `name`;
- генерация TS-типов из `app/DTO/**` + `make types-check`, чтобы генерат не разъезжался;
- URL только с бэкенда (ADR 0002) — строже, чем в spa, где половина хелперов на ziggy;
- фильтры через `spatie/laravel-query-builder` и query string;
- `FlashBag`, читающий оба текущих канала флеша;
- `NavigationBuilder` — одно дерево навигации на два рендерера;
- даты форматируются в DTO на бэкенде — тоже строже, чем в spa.

Так что из первоначального списка «что взять» половина оказалась либо сделанной, либо запланированной. Ниже — то, что осталось.

---

## 11. Что реально остаётся взять, по убыванию отдачи

### 11.1. Типы TypeScript для ключей переводов

Главная приколюха spa — словарь переводов работает как схема: `ModelName = keyof typeof resources.ru.models`, и опечатка в ключе становится ошибкой компиляции.

У них это бесплатно: JSON-словари импортируются во фронт напрямую. У нас словари в PHP, поэтому нужен генератор — артизан-команда рядом с `typescript:transform`, которая читает `resources/lang/ru/**` и пишет `resources/js/types/translations.d.ts`. `make types-check` (уже запланирован) накрывает и его без доработок.

Что это даёт помимо опечаток:

- **ловит использование нешипящихся ключей.** На фронт едут только 12 групп; `t('sicp.chapter.1')` сейчас молча вернёт ключ строкой, а с типами станет красным CI. Это ровно тот риск, ради которого в ADR 0003 написано «никогда не шипить `sicp`, `exercise`, `exercises/**`» — сейчас это правило держится на дисциплине;
- **ловит расхождение ru/en.** Если генератор сверяет наборы ключей двух локалей, отсутствующий `resources/lang/ru/pagination.php` (уже известная дырка) находится сам, а не глазами;
- **типизирует `scope`** — у spa это `Paths<typeof resources.ru.views>`, и `tView('.title')` проверяется на существование `<scope>.title`.

Не требует ни Inertia, ни Mantine, ни ziggy — работает от текущего состояния. Усиливает уже принятые ADR 0003 и решение про `scope`.

### 11.2. Флеш по ключу, а не текстом

Сейчас текст флеша считается в контроллере:

```php
$flash = $user->save()
    ? ['success' => __('account.account_updated')]
    : ['error' => __('layout.flash.error')];
```

Ключ придумывается вручную и живёт далеко от словаря; в `Admin/UserController.php:44` вообще лежит нелокализованный литерал `'User updated'` (план чинит его на общий `layout.flash.success` — то есть один текст на все экшены).

В spa контроллер пишет только тип:

```php
$this->flash('success');   // ключ = flash.<scope>.<action>.success
```

Раз `scope` и так появляется в shared-пропах, ключ флеша считается бесплатно. Разный текст на разных экшенах — без единой строки в PHP.

Наша поправка та же, что и со `scope`: строить ключ от action-name и **падать на `layout.flash.<type>`, если конкретного ключа в словаре нет**. Фоллбек делает переход постепенным — можно начать с двух-трёх экшенов. Место для этого — `FlashBag`, который и так пишется в PR 1.

### 11.3. Правила валидации в пропах → `required` и `maxlength` сами

План говорит «`required` — по пропу», то есть руками на каждом поле. В spa правила приезжают с бэка и поле само решает:

```tsx
const starred = (_.get(validationRules, name, []) as string[]).includes('required')
```

У нас это дешевле, чем у них: входные DTO уже на `spatie/laravel-data`, у которых `::getValidationRules([])` есть из коробки. Нужен только их приём из `ViewValidationRules`: прогнать правила через `Validator::make([], $rules)->getRules()` и развернуть объекты-правила в строки, иначе они не переживут JSON.

Отдача: обязательность поля перестаёт быть продублированной в разметке (и, значит, врать). Попутно из тех же правил берутся `maxlength` из `max:255` и `type="email"`.

### 11.4. Хелпер `route()` в тестах, подставляющий локаль

`CollegeTestCase::route()` подставляет тенанта в каждый вызов. Наш аналог — локаль:

```php
protected function route(string $name, array $parameters = [], ?string $locale = null): string
```

`tests/ControllerTestCase.php` сейчас умеет только создавать пользователя. Хелпер снимает шум и защищает ровно от того класса багов, ради которого написан ADR 0002 — и заодно даёт дешёвый способ прогнать страницу в обеих локалях, что в плане проверки записано ручным пунктом.

### 11.5. Мапы из `resources/js/common/` — в словари

`common/checkStatusMap.js`, `tabNameKeysMap.js`, `tabNamesMap.js`, `hashLocationMap.js` — это справочники, живущие в JS отдельно от переводов. В spa такие вещи лежат в словаре, а опции для селектов достаются прямо оттуда:

```ts
const items = i18n.t(`enums.${enumName}`, { returnObjects: true })
```

Список значений не гоняется с бэка и не дублируется в двух местах. Для нас это часть работы фазы 2 по редактору (там же переезжают ~20 его ключей), но зафиксировать направление стоит сейчас.

### 11.6. Раскладка ресурса `Index/Create/Edit/Fields/Menu`

Конвенция хорошая, но честно: в фазе 1 она почти не окупается. CRUD с парой create/edit у нас один (`admin/users`, причём create нет), а `Menu.tsx` перекрывается `NavigationBuilder`. Смысл появляется, когда возникнет второй ресурс с формами — тогда `Fields.tsx` не даст create и edit разъехаться. Записать как конвенцию на будущее, не как задачу.

### 11.7. Мелочи, которые стоят своих строк

- **`assertFieldsEqual(array $fields, Model $model)`** в `TestCase` — сравнение пачки полей с моделью, с разворачиванием энумов;
- **`Inertia::defer` / `Inertia::optional`** для тяжёлых справочников — в плане уже упомянут для `solution/index` (~15 КБ списка упражнений в каждом рендере), стоит применять и дальше;
- **зонтичная `make sync`** — когда генератов станет два (типы DTO + типы переводов), одна цель избавит от «забыл перегенерировать».

Что **не** берём: `fixSequenceId` (у нас нет сидов с явными id), FSM-`canTransitionTo` (переход всего один и на фронт не выходит), фронтовые презентеры `methods/*` (мы решили форматировать в DTO на бэкенде — строже), деньги/brick (нет денег в домене).

---

## 12. Генератора CRUD в spa нет

Отдельный вопрос, который стоило проверить: генератора ресурсов там нет вообще. Ни `stubs/`, ни артизан-команд (единственная — `GenerateContract`, и она про договоры), ни пакетов вроде blueprint/scaffold. `make sync-code` генерирует только производное — типы, ide-helper, роуты, переводы — но не код ресурсов.

При этом проект к генератору полностью готов, и это видно на цифрах. Четыре админских ресурса (`InvoiceType`, `MarketingDiscount`, `Campus`, `SocialDiscount`) — 1257 строк на 20 файлов. Если в двух разных `Menu.tsx` нормализовать имя сущности (`InvoiceType` → `E`, `invoice_types` → `e_s`, …), файлы совпадают **побайтово** — `diff` возвращает пустоту. `Create.tsx` и `Edit.tsx` отличаются друг от друга тремя строками: метод (`post`/`put`), URL и подпись кнопки. Уникален по сути только `Fields.tsx` (24–43 строки) и разметка таблицы в `Index.tsx`.

То есть примерно две трети кода ресурса — шаблон, который копируют руками. Конвенции настолько жёсткие, что копипаста работает без ошибок, и именно поэтому генератор так и не написали.

Вывод для нас: **генератор сейчас преждевременен** — в фазе 1 нет и двух однотипных CRUD. Но выводы из их опыта два:

1. Конвенцию раскладки ресурса стоит зафиксировать **до** появления второго CRUD, а не после — тогда генератор (или просто копипаста) будет иметь что копировать.
2. Признак, что пора писать генератор: третий ресурс, у которого `Menu`/`Create`/`Edit` отличаются только именем сущности. Проверяется тем же способом — `sed`-нормализация имён и `diff`.
