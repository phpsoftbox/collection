# PhpSoftBox Collection

`PhpSoftBox\Collection\Collection` — небольшая, строго типизированная коллекция с fluent API для удобной работы с массивами: выборка, трансформации, сортировка, работа с вложенными структурами через dot-нотацию и слияние.

Быстрый пример:

```php
use PhpSoftBox\Collection\Collection;

$collection = Collection::from([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
]);

$names = $collection
    ->map(fn (array $row) => $row['name'])
    ->all();
```

## Оглавление
- [Создание и базовые операции](#создание-и-базовые-операции)
- [Операции с ключами верхнего уровня](#операции-с-ключами-верхнего-уровня)
- [Dot-нотация: вложенные ключи](#dot-нотация-вложенные-ключи)
- [Выборка / исключение / нормализация ключей](#выборка--исключение--нормализация-ключей)
- [Сравнение наборов](#сравнение-наборов)
- [Уникальность и индексирование](#уникальность-и-индексирование)
- [Разбиение на чанки](#разбиение-на-чанки)
- [Сортировка](#сортировка)
- [Вставка элементов](#вставка-элементов)
- [Функциональные операции](#функциональные-операции)
- [Фильтрация where](#фильтрация-where)
- [Математические операции](#математические-операции)
- [Dot helpers: dot/undot](#dot-helpers-dotundot)
- [Слияние](#слияние)
- [Итерация и Countable](#итерация-и-countable)

---

## Создание и базовые операции

### `__construct(array $items = [])`
Создаёт коллекцию из массива.

Пример:
```php
use PhpSoftBox\Collection\Collection;

$c = new Collection([1, 2, 3]);
```

### `public static function from(array $items): self`
Фабричный метод для создания коллекции из массива.

Пример:
```php
use PhpSoftBox\Collection\Collection;

$c = Collection::from(['a' => 1, 'b' => 2]);
```

### `public function all(): array`
Возвращает исходный массив элементов.

Пример:
```php
$items = Collection::from([1, 2])->all();
// [1, 2]
```

### `public function toArray(): array`
Синоним `all()`.

Пример:
```php
$items = Collection::from([1, 2])->toArray();
```

---

## Операции с ключами верхнего уровня

### `public function add(string|int $key, mixed $value): self`
Устанавливает значение по верхнеуровневому ключу и возвращает `$this`.

Параметры:
- `$key` — ключ.
- `$value` — значение.

Пример:
```php
$c = Collection::from([])
    ->add('name', 'Alice')
    ->add('age', 30);

// ['name' => 'Alice', 'age' => 30]
$items = $c->all();
```

### `public function has(string|int $key): bool`
Проверяет наличие верхнеуровневого ключа.

Пример:
```php
$c = Collection::from(['a' => 1]);

$c->has('a'); // true
$c->has('b'); // false
```

### `public function get(string|int $key, mixed $default = null): mixed`
Возвращает значение по ключу. Если ключ отсутствует — возвращает `$default`.

Пример:
```php
$c = Collection::from(['a' => 1]);

$c->get('a');        // 1
$c->get('missing');  // null
$c->get('missing', 0); // 0
```

### `public function remove(string|int $key): self`
Удаляет ключ верхнего уровня и возвращает `$this`.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2])
    ->remove('a');

$c->all(); // ['b' => 2]
```

### `public function keys(): array`
Возвращает список ключей верхнего уровня.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2]);

$c->keys(); // ['a', 'b']
```

---

## Dot-нотация: вложенные ключи

### `public function getPath(string $path, mixed $default = null): mixed`
Получает значение по пути в dot-нотации (`a.b.c`). Если путь не найден — возвращает `$default`.

Пример:
```php
$c = Collection::from(['db' => ['host' => 'localhost']]);

$c->getPath('db.host'); // 'localhost'
$c->getPath('db.port', 3306); // 3306
```

### `public function hasPath(string $path): bool`
Проверяет существование пути в dot-нотации (`a.b.c`).

Пример:
```php
$c = Collection::from(['db' => ['host' => 'localhost']]);

$c->hasPath('db.host'); // true
$c->hasPath('db.port'); // false
```

### `public function setPath(string $path, mixed $value): self`
Устанавливает значение по пути в dot-нотации (`a.b.c`). Промежуточные массивы создаются автоматически.

Особенность: пустой путь (`""`) игнорируется.

Пример:
```php
$c = Collection::from([])
    ->setPath('db.host', 'localhost')
    ->setPath('db.port', 3306);

$c->all();
// ['db' => ['host' => 'localhost', 'port' => 3306]]
```

### `public function forget(string|array $paths): self`
Удаляет ключ(и) по пути/путям в dot-нотации.

Параметры:
- `$paths` — строка пути или массив путей.

Пример:
```php
$c = Collection::from([
    'db' => ['host' => 'localhost', 'port' => 3306],
    'debug' => true,
]);

$c->forget(['db.port', 'debug']);

$c->all();
// ['db' => ['host' => 'localhost']]
```

---

## Выборка / исключение / нормализация ключей

### `public function only(array $keys): self`
Возвращает новую коллекцию, в которой оставлены только указанные верхнеуровневые ключи.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2, 'c' => 3]);

$c->only(['a', 'c'])->all();
// ['a' => 1, 'c' => 3]
```

### `public function pick(array $keys): self`
Синоним `only()`.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2]);

$c->pick(['b'])->all();
// ['b' => 2]
```

### `public function except(array $keys): self`
Возвращает новую коллекцию, исключив указанные верхнеуровневые ключи.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2, 'c' => 3]);

$c->except(['b'])->all();
// ['a' => 1, 'c' => 3]
```

### `public function values(): self`
Возвращает новую коллекцию со значениями, переиндексированными с 0.

Полезно после `filter()`, если нужна плотная нумерация.

Пример:
```php
$c = Collection::from([10 => 'a', 20 => 'b']);

$c->values()->all();
// ['a', 'b']
```

### `public function resetKeys(): self`
Синоним `values()`.

Пример:
```php
$c = Collection::from([10 => 'a'])->resetKeys()->all();
// ['a']
```

---

## Сравнение наборов

### `public function intersect(array|Collection $items): self`
Оставляет элементы, которые присутствуют в `$items`.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2, 'c' => 3]);

$c->intersect([2, 3])->all();
// ['b' => 2, 'c' => 3]
```

### `public function diff(array|Collection $items): self`
Удаляет элементы, которые присутствуют в `$items`.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2, 'c' => 3]);

$c->diff([2, 3])->all();
// ['a' => 1]
```

---

## Уникальность и индексирование

### `public function unique(string|callable|null $by = null): self`
Возвращает новую коллекцию без дубликатов.

Правила:
- `$by` как `string`: уникальность по полю массива или свойству/геттеру объекта.
- `$by` как `callable`: уникальность по вычисленному ключу.
- `$by === null`: сравнение по значению (для скаляров напрямую, для сложных типов — через JSON-представление).

Примеры:

Уникальные значения:
```php
Collection::from([1, 1, 2, 2, 3])->unique()->all();
// [1, 2, 3]
```

Уникальность по полю массива:
```php
$users = Collection::from([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 1, 'name' => 'Alice v2'],
    ['id' => 2, 'name' => 'Bob'],
]);

$users->unique('id')->all();
// оставит только первую запись для каждого id
```

Уникальность по функции:
```php
$items = Collection::from(['a', 'A', 'b']);

$items->unique(fn (string $v) => strtolower($v))->all();
// ['a', 'b']
```

### `public function duplicates(string|callable|null $by = null, bool $strict = false): self`
Возвращает дубликаты (начиная со второй встречи значения).

Пример:
```php
Collection::from([1, 2, 1, 1])->duplicates()->all();
// [2 => 1, 3 => 1]
```

Пример с ключом:
```php
$users = Collection::from([
    ['id' => 1, 'email' => 'a@test'],
    ['id' => 2, 'email' => 'b@test'],
    ['id' => 3, 'email' => 'a@test'],
]);

$users->duplicates('email')->all();
// [2 => 'a@test']
```

### `public function indexBy(string|callable $key): self`
Переиндексирует элементы по полю/свойству/геттеру (если `$key` — строка) или по результату callable.

Пример (по полю массива):
```php
$users = Collection::from([
    ['id' => 10, 'name' => 'Alice'],
    ['id' => 20, 'name' => 'Bob'],
]);

$indexed = $users->indexBy('id')->all();
// ['10' => ['id' => 10, ...], '20' => ['id' => 20, ...]]
```

---

## Разбиение на чанки

### `public function chunk(int $size): self`
Разбивает коллекцию на чанки заданного размера и возвращает новую коллекцию, где каждый элемент — подмассив.

Особенности:
- `$size < 1` — вернёт один чанк со всеми элементами.
- Ключи внутри чанков сохраняются.

Пример:
```php
$c = Collection::from(['a' => 1, 'b' => 2, 'c' => 3]);

$chunks = $c->chunk(2)->all();
// [
//   ['a' => 1, 'b' => 2],
//   ['c' => 3],
// ]
```

---

## Сортировка

### `public function sortByValues(bool $desc = false, int $flags = SORT_REGULAR): self`
Сортирует по значениям и возвращает новую коллекцию.

Параметры:
- `$desc` — сортировать по убыванию.
- `$flags` — флаги сортировки (по умолчанию `SORT_REGULAR`).

Пример:
```php
$c = Collection::from(['b' => 2, 'a' => 1]);

$c->sortByValues()->all();
// ['a' => 1, 'b' => 2] (по значениям)
```

### `public function sortByKeys(bool $desc = false, int $flags = SORT_REGULAR): self`
Сортирует по ключам и возвращает новую коллекцию.

Пример:
```php
$c = Collection::from(['b' => 1, 'a' => 2]);

$c->sortByKeys()->all();
// ['a' => 2, 'b' => 1]
```

### `public function sortBy(string|callable $by, bool $desc = false): self`
Сортирует по извлекаемому значению.

`$by`:
- `string`: берётся поле массива/свойство объекта/геттер.
- `callable`: вычисляет значение для сравнения.

Пример (по полю массива):
```php
$users = Collection::from([
    ['id' => 2, 'name' => 'Bob'],
    ['id' => 1, 'name' => 'Alice'],
]);

$users->sortBy('id')->all();
// Alice, затем Bob
```

Пример (по функции, по убыванию):
```php
$c = Collection::from(['aaa', 'b', 'cc']);

$c->sortBy(fn (string $s) => strlen($s), desc: true)->all();
// ['aaa', 'cc', 'b']
```

---

## Вставка элементов

### `public function insertAt(int $position, string|int $key, mixed $value): self`
Вставляет пару ключ/значение на позицию `$position` (0-based) и возвращает `$this`.

Особенности:
- Отрицательные позиции приводятся к 0.
- Если позиция больше размера коллекции — добавит в конец.

Пример:
```php
$c = Collection::from(['a' => 1, 'c' => 3]);

$c->insertAt(1, 'b', 2);
$c->all();
// ['a' => 1, 'b' => 2, 'c' => 3]
```

### `public function insertBefore(string|int $beforeKey, string|int $key, mixed $value): self`
Вставляет пару перед ключом `$beforeKey` и возвращает `$this`.

Если `$beforeKey` не найден — добавит в конец.

Пример:
```php
$c = Collection::from(['b' => 2, 'c' => 3]);

$c->insertBefore('b', 'a', 1);
$c->all();
// ['a' => 1, 'b' => 2, 'c' => 3]
```

### `public function insertAfter(string|int $afterKey, string|int $key, mixed $value): self`
Вставляет пару после ключа `$afterKey` и возвращает `$this`.

Если `$afterKey` не найден — добавит в конец.

Пример:
```php
$c = Collection::from(['a' => 1, 'c' => 3]);

$c->insertAfter('a', 'b', 2);
$c->all();
// ['a' => 1, 'b' => 2, 'c' => 3]
```

---

## Функциональные операции

### `public function map(callable $fn): self`
Преобразует каждый элемент функцией и возвращает новую коллекцию.

Пример:
```php
$c = Collection::from([1, 2, 3]);

$c->map(fn (int $v) => $v * 10)->all();
// [10, 20, 30]
```

### `public function filter(callable $fn): self`
Фильтрует элементы предикатом и возвращает новую коллекцию.

Особенность: после фильтрации выполняется переиндексация `array_values()`, поэтому ключи становятся 0..N.

Пример:
```php
$c = Collection::from([1, 2, 3, 4]);

$c->filter(fn (int $v) => $v % 2 === 0)->all();
// [2, 4]
```

### `public function reduce(callable $fn, mixed $initial = null): mixed`
Сворачивает элементы в одно значение.

Параметры:
- `$fn` — `(mixed $carry, mixed $item): mixed`.
- `$initial` — начальное значение аккумулятора.

Пример:
```php
$c = Collection::from([1, 2, 3]);

$sum = $c->reduce(fn (int $carry, int $v) => $carry + $v, 0);
// 6
```

### `public function first(?callable $fn = null, mixed $default = null): mixed`
Возвращает первый элемент.

Режимы:
- Без `$fn`: вернёт первый элемент коллекции (по текущему порядку) или `$default`, если коллекция пустая.
- С `$fn`: вернёт первый элемент, для которого `$fn($item) === true`, иначе `$default`.

Пример:
```php
$c = Collection::from([10, 20, 30]);

$c->first(); // 10
$c->first(fn (int $v) => $v > 15); // 20
$c->first(fn (int $v) => $v > 100, default: -1); // -1
```

### `public function push(mixed ...$values): self`
Добавляет значения в конец коллекции (как `array_push`) и возвращает `$this`.

Пример:
```php
$c = Collection::from([1]);

$c->push(2, 3);
$c->all();
// [1, 2, 3]
```

---

## Фильтрация where

### `public function where(string $key, mixed $operator = null, mixed $value = null): self`
Фильтрует по ключу с поддержкой операторов (`=`, `!=`, `>`, `<`, `>=`, `<=`, `===`, `!==`).

Пример:
```php
$c = Collection::from([
    ['id' => 1, 'score' => 10],
    ['id' => 2, 'score' => 20],
]);

$c->where('score', '>', 10)->all();
// [['id' => 2, 'score' => 20]]
```

### `public function whereStrict(string $key, mixed $value): self`
Строгое сравнение (`===`) по ключу.

### `public function whereIn(string $key, array $values, bool $strict = false): self`
Оставляет элементы, где значение ключа входит в `$values`.

### `public function whereNotIn(string $key, array $values, bool $strict = false): self`
Оставляет элементы, где значение ключа не входит в `$values`.

### `public function whereBetween(string $key, array $values): self`
Оставляет элементы с значением между `[min, max]`.

### `public function whereNotBetween(string $key, array $values): self`
Оставляет элементы с значением вне диапазона `[min, max]`.

### `public function whereNull(string $key): self`
Оставляет элементы, где значение `null`.

### `public function whereNotNull(string $key): self`
Оставляет элементы, где значение не `null`.

---

## Математические операции

### `public function sum(string|callable|null $by = null): int|float`
Суммирует значения (опционально по ключу/коллбэку).

### `public function average(string|callable|null $by = null): int|float|null`
Среднее арифметическое. Синоним: `avg()`.

### `public function median(string|callable|null $by = null): int|float|null`
Медиана набора чисел.

### `public function percentile(float|int $percent, string|callable|null $by = null): int|float|null`
Перцентиль (0..100). Использует линейную интерполяцию.

### `public function percentage(callable|string|null $by = null): float`
Процент элементов, удовлетворяющих условию/ключу.

### `public function min(string|callable|null $by = null): int|float|null`
Минимум среди числовых значений.

### `public function max(string|callable|null $by = null): int|float|null`
Максимум среди числовых значений.

---

## Dot helpers: dot/undot

### `public function dot(string $prepend = ''): self`
Преобразует вложенный массив в плоский массив с dot-ключами.

Параметры:
- `$prepend` — префикс для всех ключей (опционально).

Пример:
```php
$c = Collection::from([
    'db' => ['host' => 'localhost', 'port' => 3306],
]);

$c->dot()->all();
// ['db.host' => 'localhost', 'db.port' => 3306]
```

### `public static function undot(array $flat): self`
Собирает вложенный массив из плоского массива с dot-ключами.

Пример:
```php
use PhpSoftBox\Collection\Collection;

$nested = Collection::undot([
    'db.host' => 'localhost',
    'db.port' => 3306,
])->all();

// ['db' => ['host' => 'localhost', 'port' => 3306]]
```

---

## Слияние

### `public function merge(array|Collection $other, bool|array $options = true): self`
Сливает текущие элементы с другим массивом/коллекцией и возвращает новую коллекцию.

Параметры:
- `$other` — массив или другая `Collection`.
- `$options`:
  - `true|false`: включает/выключает рекурсивное слияние.
  - `array`: расширенные настройки:
    - `recursive` (bool, по умолчанию `true`) — рекурсивное слияние по ключам.
    - `list` (`replace|append|append_unique`, по умолчанию `replace`) — стратегия для списков (когда обе стороны — списки).

Стратегии `list`:
- `replace`: правый список заменяет левый.
- `append`: правый список дописывается в конец левого.
- `append_unique`: дописывается только то, чего ещё нет (строгое сравнение).

Пример (рекурсивно + append для списков):
```php
$a = Collection::from([
    'db' => ['hosts' => ['a', 'b']],
]);

$b = [
    'db' => ['hosts' => ['c']],
];

$out = $a->merge($b, ['recursive' => true, 'list' => 'append'])->all();
// ['db' => ['hosts' => ['a', 'b', 'c']]]
```

Пример (нерекурсивно):
```php
$c = Collection::from([
    'db' => ['host' => 'localhost'],
    'debug' => false,
]);

$out = $c->merge(['db' => ['host' => 'override']], false)->all();
// 'db' будет полностью заменён
```

---

## Итерация и Countable

### `public function getIterator(): Traversable`
Возвращает итератор, чтобы коллекцию можно было использовать в `foreach`.

Пример:
```php
$c = Collection::from([1, 2, 3]);

foreach ($c as $v) {
    // ...
}
```

### `public function count(): int`
Возвращает количество элементов. Позволяет использовать `count($collection)`.

Пример:
```php
$c = Collection::from([1, 2, 3]);

count($c); // 3
```
