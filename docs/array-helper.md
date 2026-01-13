# ArrayHelper

`PhpSoftBox\Collection\ArrayHelper` — набор статических утилит для работы с массивами, близкий по смыслу к `Illuminate\Support\Arr`.

Ниже перечислены все методы и их назначение. Примеры минимальные — чтобы быстро понять поведение.

## Базовые операции

### `only(array $items, array $keys): array`
Возвращает только указанные ключи верхнего уровня.

### `except(array $items, array $keys): array`
Возвращает массив без указанных ключей верхнего уровня.

### `add(array $array, string|int $key, mixed $value): array`
Добавляет ключ, если его ещё нет.

### `exists(array|ArrayAccess $array, string|int $key): bool`
Проверяет существование ключа (для массива и `ArrayAccess`).

### `accessible(mixed $value): bool`
Проверяет, можно ли обращаться к значению как к массиву.

## Dot-нотация и пути

### `getPath(array $items, string $path, mixed $default = null): mixed`
Получает значение по dot-пути. Поддерживает `*` wildcard.

### `hasPath(array $items, string $path): bool`
Проверяет наличие пути. Поддерживает `*` wildcard.

### `setPath(array $items, string $path, mixed $value): array`
Устанавливает значение по dot-пути, создавая вложенные массивы.

### `forget(array $items, string|array $paths): array`
Удаляет ключ(и) по dot-пути.

### `dot(array $items, string $prepend = ''): array`
Преобразует вложенный массив в плоский по dot-ключам.

### `undot(array $flat): array`
Собирает вложенный массив из плоского (dot-ключи).

### `path(array $items, string $pattern): array`
Возвращает все совпадения по шаблону пути с `*`.

### `pathMatches(string $pattern, string $path): bool`
Проверяет, совпадает ли путь с шаблоном.

### `get(array|ArrayAccess $array, string|int|array|null $key, mixed $default = null): mixed`
Получает значение по ключу (поддерживает dot-нотацию и `*`).

### `has(array $array, string|array $keys): bool`
Проверяет наличие всех ключей (dot-нотация и `*`).

### `hasAll(array $array, string|array $keys): bool`
Синоним `has`.

### `hasAny(array $array, string|array $keys): bool`
Проверяет, что есть хотя бы один ключ из списка.

## Преобразования типов

### `array(array $array, string|int|null $key, array $default = []): array`
Возвращает значение как массив или `$default`.

### `boolean(array $array, string|int|null $key, bool $default = false): bool`
Возвращает значение как bool, учитывая строки `true/false`.

### `integer(array $array, string|int|null $key, int $default = 0): int`
Возвращает значение как int (если возможно).

### `float(array $array, string|int|null $key, float $default = 0.0): float`
Возвращает значение как float (если возможно).

### `string(array $array, string|int|null $key, string $default = ''): string`
Возвращает значение как string (если возможно).

### `from(mixed $items): array`
Преобразует `Traversable` или скаляр в массив.

## Структурные операции

### `collapse(array $array): array`
Склеивает массив массивов в один.

### `crossJoin(array ...$arrays): array`
Декартово произведение массивов.

### `divide(array $array): array`
Возвращает `[keys, values]`.

### `flatten(array $array, int|float $depth = INF): array`
Плоский массив с указанной глубиной.

### `isAssoc(array $array): bool`
Проверяет, что массив ассоциативный.

### `isList(array $array): bool`
Проверяет, что массив — список.

### `join(array $array, string $glue, string $finalGlue = ''): string`
Склеивает значения строкой и финальным разделителем.

## Итерации и выборки

### `every(array $array, ?callable $callback = null): bool`
Проверяет, что все элементы удовлетворяют условию.

### `some(array $array, ?callable $callback = null): bool`
Проверяет, что есть хотя бы один элемент по условию.

### `first(array $array, ?callable $callback = null, mixed $default = null): mixed`
Возвращает первый элемент (или по условию).

### `last(array $array, ?callable $callback = null, mixed $default = null): mixed`
Возвращает последний элемент (или по условию).

### `map(array $array, callable $callback): array`
Отображает значения функцией.

### `mapSpread(array $array, callable $callback): array`
Разворачивает элементы-массивы в аргументы коллбэка.

### `mapWithKeys(array $array, callable $callback): array`
Формирует новые ключи/значения из коллбэка.

### `partition(array $array, callable|string $callback): array`
Разделяет на две группы: прошедшие и не прошедшие.

### `reject(array $array, callable|bool $callback = true): array`
Отбрасывает элементы, удовлетворяющие условию.

### `where(array $array, callable $callback): array`
Оставляет элементы по условию.

### `whereNotNull(array $array): array`
Удаляет `null` значения.

### `select(array $array, array|string $keys): array`
Выбирает набор ключей у каждого элемента.

## Индексация и извлечение

### `keyBy(array $array, callable|string $keyBy): array`
Переиндексирует по ключу/коллбэку.

### `pluck(array $array, string|int $value, string|int|null $key = null): array`
Извлекает значения по ключу (опционально — с новыми ключами).

## Мутации массива

### `prepend(array $array, mixed $value, string|int|null $key = null): array`
Добавляет элемент в начало.

### `prependKeysWith(array $array, string $prependWith): array`
Добавляет префикс ко всем ключам.

### `pull(array &$array, string|int|null $key, mixed $default = null): mixed`
Получает значение и удаляет его из массива.

### `push(array $array, mixed $value, string|int|null $key = null): array`
Добавляет значение в конец или по ключу (если ключ — dot-путь).

### `set(array $array, string|int|null $key, mixed $value): array`
Ставит значение по ключу (dot-путь).

### `shuffle(array $array, ?int $seed = null): array`
Перемешивает массив (опционально с seed).

### `take(array $array, int $limit): array`
Берёт первые `$limit` элементов (или последние — если `$limit < 0`).

### `wrap(mixed $value): array`
Оборачивает значение в массив.

## Прочее

### `query(array $array): string`
Формирует query-строку.

### `random(array $array, ?int $number = null, bool $preserveKeys = false): mixed`
Возвращает случайный элемент или список случайных элементов.

### `sole(array $array, ?callable $callback = null): mixed`
Возвращает единственный элемент, иначе бросает исключение.

### `sort(array $array, ?callable $callback = null): array`
Сортирует по значениям (с коллбэком или без).

### `sortDesc(array $array, ?callable $callback = null): array`
Сортирует по значениям по убыванию.

### `sortRecursive(array $array): array`
Рекурсивная сортировка массива.

### `toCssClasses(array|string $classes): string`
Формирует строку CSS-классов.

### `toCssStyles(array|string $styles): string`
Формирует строку CSS-стилей.
