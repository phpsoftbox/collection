<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

use function array_chunk;
use function array_diff;
use function array_filter;
use function array_intersect;
use function array_is_list;
use function array_key_exists;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_map;
use function array_merge;
use function array_push;
use function array_reduce;
use function array_replace;
use function array_values;
use function arsort;
use function asort;
use function ceil;
use function count;
use function explode;
use function floor;
use function gettype;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function json_encode;
use function krsort;
use function ksort;
use function max;
use function method_exists;
use function min;
use function property_exists;
use function sort;
use function str_contains;
use function str_replace;
use function uasort;
use function ucwords;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const SORT_REGULAR;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
class Collection implements IteratorAggregate, Countable
{
    /**
     * @param array<TKey, TValue> $items Инициализационный массив
     */
    public function __construct(
        /** @var array<TKey, TValue> */
        private array $items = [],
    ) {
    }

    /**
     * Создать коллекцию из массива
     */
    public static function from(array $items): self
    {
        return new self($items);
    }

    /**
     * Получить все элементы
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Преобразовать в массив (синоним all)
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    // --- Ассоциативные операции уровня верхних ключей ---

    /**
     * Добавить/установить значение по верхнеуровневому ключу
     * @return $this
     */
    public function add(string|int $key, mixed $value): self
    {
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Проверить наличие верхнеуровневого ключа
     */
    public function has(string|int $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Получить значение по верхнеуровневому ключу
     *
     * @return TValue|null
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Удалить верхнеуровневый ключ
     * @return $this
     */
    public function remove(string|int $key): self
    {
        unset($this->items[$key]);

        return $this;
    }

    // --- Dot-нотация (вложенные ключи вида a.b.c) ---

    /**
     * Получить значение по пути (a.b.c). При wildcard возвращает список значений.
     */
    public function getPath(string $path, mixed $default = null): mixed
    {
        return ArrayHelper::getPath($this->items, $path, $default);
    }

    /**
     * Проверить наличие пути (a.b.c). При wildcard проверяет наличие совпадений.
     */
    public function hasPath(string $path): bool
    {
        return ArrayHelper::hasPath($this->items, $path);
    }

    /**
     * Установить значение по пути (a.b.c), создавая вложенные массивы
     * @return $this
     */
    public function setPath(string $path, mixed $value): self
    {
        $this->items = ArrayHelper::setPath($this->items, $path, $value);

        return $this;
    }

    /**
     * Удалить ключ(и) по пути/путям (поддерживает массив путей)
     * @return $this
     */
    public function forget(string|array $paths): self
    {
        $this->items = ArrayHelper::forget($this->items, $paths);

        return $this;
    }

    /**
     * Вернуть только указанные верхнеуровневые ключи
     */
    public function only(array $keys): self
    {
        return new self(ArrayHelper::only($this->items, $keys));
    }

    /**
     * Синоним only
     */
    public function pick(array $keys): self
    {
        return $this->only($keys);
    }

    /**
     * Вернуть всё, кроме указанных верхнеуровневых ключей
     */
    public function except(array $keys): self
    {
        return new self(ArrayHelper::except($this->items, $keys));
    }

    /**
     * Оставить элементы, присутствующие в $items
     */
    public function intersect(array|Collection $items): self
    {
        $values = $items instanceof self ? $items->all() : $items;
        $result = array_intersect($this->items, $values);

        return new self($result);
    }

    /**
     * Удалить элементы, присутствующие в $items
     */
    public function diff(array|Collection $items): self
    {
        $values = $items instanceof self ? $items->all() : $items;
        $result = array_diff($this->items, $values);

        return new self($result);
    }

    /**
     * Вернуть массив значений с переиндексацией
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * Сбросить ключи (синоним values)
     */
    public function resetKeys(): self
    {
        return $this->values();
    }

    // --- Уникальность ---

    /**
     * Удалить дубликаты элементов
     * - $by = string: взять поле массива/свойство объекта
     * - $by = callable: вычислить ключ уникальности
     * - null: сравнение по значению (нестрогая сериализация для сложных типов)
     */
    public function unique(string|callable|null $by = null): self
    {
        $seen   = [];
        $result = [];
        foreach ($this->items as $item) {
            $key = null;
            if (is_string($by)) {
                $key = $this->extract($item, $by);
            } elseif (is_callable($by)) {
                $key = $by($item);
            } else {
                $key = is_scalar($item) ? $item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $hash = is_scalar($key) ? (string) $key : json_encode($key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!array_key_exists($hash, $seen)) {
                $seen[$hash] = true;
                $result[]    = $item;
            }
        }

        return new self($result);
    }

    /**
     * Вернуть дубликаты значений (как в Laravel Collection::duplicates)
     */
    public function duplicates(string|callable|null $by = null, bool $strict = false): self
    {
        $duplicates = [];
        $seen       = [];

        foreach ($this->items as $key => $item) {
            $value = $this->valueOf($item, $by, $key);
            $hash  = $this->hashValue($value, $strict);

            if (array_key_exists($hash, $seen)) {
                $duplicates[$key] = $value;
                continue;
            }

            $seen[$hash] = true;
        }

        return new self($duplicates);
    }

    // --- Индексирование по колонке ---

    /**
     * Переиндексировать элементы по колонке/функции
     */
    public function indexBy(string|callable $key): self
    {
        $out = [];
        foreach ($this->items as $item) {
            $idx                = is_string($key) ? $this->extract($item, $key) : $key($item);
            $out[(string) $idx] = $item;
        }

        return new self($out);
    }

    // --- Разбиение на чанки ---

    /**
     * Разбить массив на чанки указанного размера
     */
    public function chunk(int $size): self
    {
        if ($size < 1) {
            return new self([$this->items]);
        }

        return new self(array_chunk($this->items, $size, true));
    }

    // --- Сортировка ---

    /**
     * Сортировка по значениям
     */
    public function sortByValues(bool $desc = false, int $flags = SORT_REGULAR): self
    {
        $copy = $this->items;
        if ($desc) {
            arsort($copy, $flags);
        } else {
            asort($copy, $flags);
        }

        return new self($copy);
    }

    /**
     * Сортировка по ключам
     */
    public function sortByKeys(bool $desc = false, int $flags = SORT_REGULAR): self
    {
        $copy = $this->items;
        if ($desc) {
            krsort($copy, $flags);
        } else {
            ksort($copy, $flags);
        }

        return new self($copy);
    }

    /**
     * Сортировка по извлекаемому значению (поле или функция)
     */
    public function sortBy(string|callable $by, bool $desc = false): self
    {
        $copy = $this->items;
        uasort($copy, function ($a, $b) use ($by, $desc) {
            $va = is_string($by) ? $this->extract($a, $by) : $by($a);
            $vb = is_string($by) ? $this->extract($b, $by) : $by($b);
            if ($va == $vb) {
                return 0;
            }

            return ($va <=> $vb) * ($desc ? -1 : 1);
        });

        return new self($copy);
    }

    // --- Вставка по позиции/до/после ключа ---

    /**
     * Вставить пару ключ/значение на позицию $position (0-based)
     * @return $this
     */
    public function insertAt(int $position, string|int $key, mixed $value): self
    {
        $pos = max(0, $position);
        $out = [];
        $i   = 0;
        foreach ($this->items as $k => $v) {
            if ($i === $pos) {
                $out[$key] = $value;
            }
            $out[$k] = $v;
            $i++;
        }
        if ($pos >= $i) {
            $out[$key] = $value;
        }
        $this->items = $out;

        return $this;
    }

    /**
     * Вставить до указанного ключа
     * @return $this
     */
    public function insertBefore(string|int $beforeKey, string|int $key, mixed $value): self
    {
        $out      = [];
        $inserted = false;
        foreach ($this->items as $k => $v) {
            if ($k === $beforeKey && !$inserted) {
                $out[$key] = $value;
                $inserted  = true;
            }
            $out[$k] = $v;
        }
        if (!$inserted) {
            $out[$key] = $value;
        }
        $this->items = $out;

        return $this;
    }

    /**
     * Вставить после указанного ключа
     * @return $this
     */
    public function insertAfter(string|int $afterKey, string|int $key, mixed $value): self
    {
        $out      = [];
        $inserted = false;
        foreach ($this->items as $k => $v) {
            $out[$k] = $v;
            if ($k === $afterKey && !$inserted) {
                $out[$key] = $value;
                $inserted  = true;
            }
        }
        if (!$inserted) {
            $out[$key] = $value;
        }
        $this->items = $out;

        return $this;
    }

    // --- Функциональные ---

    /**
     * Отобразить элементы функцией
     */
    public function map(callable $fn): self
    {
        return new self(array_map($fn, $this->items));
    }

    /**
     * Отфильтровать элементы функцией (с переиндексацией)
     */
    public function filter(callable $fn): self
    {
        return new self(array_values(array_filter($this->items, $fn)));
    }

    /**
     * Фильтрация по условию (Laravel-стиль)
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): self
    {
        [$operator, $value] = $this->normalizeWhereArgs($operator, $value);

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $actual = $this->dataGet($item, $key);

            return $this->compare($actual, $operator, $value);
        });
    }

    /**
     * Строгое сравнение по ключу
     */
    public function whereStrict(string $key, mixed $value): self
    {
        return $this->filter(function ($item) use ($key, $value) {
            return $this->dataGet($item, $key) === $value;
        });
    }

    /**
     * Значение входит в список
     */
    public function whereIn(string $key, array $values, bool $strict = false): self
    {
        return $this->filter(function ($item) use ($key, $values, $strict) {
            $actual = $this->dataGet($item, $key);

            return in_array($actual, $values, $strict);
        });
    }

    /**
     * Значение не входит в список
     */
    public function whereNotIn(string $key, array $values, bool $strict = false): self
    {
        return $this->filter(function ($item) use ($key, $values, $strict) {
            $actual = $this->dataGet($item, $key);

            return !in_array($actual, $values, $strict);
        });
    }

    /**
     * Значение находится между $min и $max (включительно)
     */
    public function whereBetween(string $key, array $values): self
    {
        [$min, $max] = $values;

        return $this->filter(function ($item) use ($key, $min, $max) {
            $actual = $this->dataGet($item, $key);

            return $actual >= $min && $actual <= $max;
        });
    }

    /**
     * Значение не находится между $min и $max
     */
    public function whereNotBetween(string $key, array $values): self
    {
        [$min, $max] = $values;

        return $this->filter(function ($item) use ($key, $min, $max) {
            $actual = $this->dataGet($item, $key);

            return $actual < $min || $actual > $max;
        });
    }

    /**
     * Значение равно null
     */
    public function whereNull(string $key): self
    {
        return $this->filter(function ($item) use ($key) {
            return $this->dataGet($item, $key) === null;
        });
    }

    /**
     * Значение не равно null
     */
    public function whereNotNull(string $key): self
    {
        return $this->filter(function ($item) use ($key) {
            return $this->dataGet($item, $key) !== null;
        });
    }

    /**
     * Свёртка элементов
     */
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $fn, $initial);
    }

    /**
     * Сумма значений
     */
    public function sum(string|callable|null $by = null): int|float
    {
        $sum = 0;

        foreach ($this->items as $key => $item) {
            $value = $this->valueOf($item, $by, $key);
            if (is_numeric($value)) {
                $sum += $value;
            }
        }

        return $sum;
    }

    /**
     * Среднее арифметическое
     */
    public function average(string|callable|null $by = null): int|float|null
    {
        $sum   = 0;
        $count = 0;

        foreach ($this->items as $key => $item) {
            $value = $this->valueOf($item, $by, $key);
            if (is_numeric($value)) {
                $sum += $value;
                $count++;
            }
        }

        if ($count === 0) {
            return null;
        }

        return (float) $sum / $count;
    }

    /**
     * Синоним average
     */
    public function avg(string|callable|null $by = null): int|float|null
    {
        return $this->average($by);
    }

    /**
     * Медиана
     */
    public function median(string|callable|null $by = null): int|float|null
    {
        $values = $this->numericValues($by);
        $count  = count($values);

        if ($count === 0) {
            return null;
        }

        sort($values);

        $middle = (int) floor(($count - 1) / 2);
        if ($count % 2 !== 0) {
            return $values[$middle];
        }

        return ($values[$middle] + $values[$middle + 1]) / 2;
    }

    /**
     * Перцентиль (0..100)
     */
    public function percentile(float|int $percent, string|callable|null $by = null): int|float|null
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Percent must be between 0 and 100.');
        }

        $values = $this->numericValues($by);
        $count  = count($values);

        if ($count === 0) {
            return null;
        }

        sort($values);

        if ($count === 1) {
            return $values[0];
        }

        $index = ($count - 1) * ($percent / 100);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);

        if ($lower === $upper) {
            return $values[$lower];
        }

        $weight = $index - $lower;

        return $values[$lower] + ($values[$upper] - $values[$lower]) * $weight;
    }

    /**
     * Процент элементов, удовлетворяющих условию
     */
    public function percentage(callable|string|null $by = null): float
    {
        $total = count($this->items);
        if ($total === 0) {
            return 0.0;
        }

        $matched = 0;
        foreach ($this->items as $key => $item) {
            $value = $this->valueOf($item, $by, $key);
            if ($value) {
                $matched++;
            }
        }

        return ($matched / $total) * 100;
    }

    /**
     * Минимум
     */
    public function min(string|callable|null $by = null): int|float|null
    {
        $values = $this->numericValues($by);
        if ($values === []) {
            return null;
        }

        return min($values);
    }

    /**
     * Максимум
     */
    public function max(string|callable|null $by = null): int|float|null
    {
        $values = $this->numericValues($by);
        if ($values === []) {
            return null;
        }

        return max($values);
    }

    /**
     * Первый элемент (по предикату или без него)
     *
     * @param callable(TValue): bool|null $fn
     * @return TValue|null
     */
    public function first(?callable $fn = null, mixed $default = null): mixed
    {
        if ($fn === null) {
            $firstKey = array_key_first($this->items);

            return $firstKey !== null ? $this->items[$firstKey] : $default;
        }
        foreach ($this->items as $item) {
            if ($fn($item)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Последний элемент (по предикату или без него)
     *
     * @param callable(TValue): bool|null $fn
     * @return TValue|null
     */
    public function last(?callable $fn = null, mixed $default = null): mixed
    {
        if ($fn === null) {
            if ($this->items === []) {
                return $default;
            }
            $lastKey = array_key_last($this->items);

            return $lastKey !== null ? $this->items[$lastKey] : $default;
        }

        $found = $default;
        foreach ($this->items as $item) {
            if ($fn($item)) {
                $found = $item;
            }
        }

        return $found;
    }

    /**
     * Добавить значения в конец (как в array_push)
     * @return $this
     */
    public function push(mixed ...$values): self
    {
        array_push($this->items, ...$values);

        return $this;
    }

    /**
     * Итератор
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Количество элементов
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Список верхнеуровневых ключей
     * @return array<int, string|int>
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    // --- Dot helpers ---

    /**
     * Преобразовать вложенный массив в плоский по точечной нотации
     * @param string $prepend Префикс, добавляемый к ключам
     */
    public function dot(string $prepend = ''): self
    {
        return new self(ArrayHelper::dot($this->items, $prepend));
    }

    /**
     * Собрать вложенный массив из плоского (dot-keys)
     */
    public static function undot(array $flat): self
    {
        return new self(ArrayHelper::undot($flat));
    }

    /**
     * Слить текущие элементы с другими
     * @param array|Collection $other Другой набор элементов
     * @param bool|array{recursive?:bool,list?:'replace'|'append'|'append_unique'} $options
     *                                                                                      - recursive: рекурсивное слияние (по умолчанию true)
     *                                                                                      - list: стратегия слияния списков (array_is_list) при recursive=true:
     *                                                                                      'replace' (по умолчанию), 'append', 'append_unique'
     * @return self Новая коллекция с результатом
     */
    public function merge(array|Collection $other, bool|array $options = true): self
    {
        $opts      = is_bool($options) ? ['recursive' => $options] : $options;
        $recursive = $opts['recursive'] ?? true;
        $listMode  = $opts['list'] ?? 'replace';

        $b = $other instanceof self ? $other->all() : $other;
        if (!$recursive) {
            return new self(array_replace($this->items, $b));
        }
        if (array_is_list($this->items) && array_is_list($b)) {
            if ($listMode === 'replace') {
                return new self($b);
            }
            if ($listMode === 'append') {
                return new self(array_merge($this->items, $b));
            }

            $acc = $this->items;
            foreach ($b as $vv) {
                if (!in_array($vv, $acc, true)) {
                    $acc[] = $vv;
                }
            }

            return new self($acc);
        }
        $out = $this->mergeArrays($this->items, $b, $listMode);

        return new self($out);
    }

    /**
     * Рекурсивное слияние массивов: правый массив перезаписывает значения,
     * для подмассивов выполняется слияние по ключам. Для списков применяется
     * стратегия $listMode: replace|append|append_unique
     * @param 'replace'|'append'|'append_unique' $listMode
     */
    private function mergeArrays(array $a, array $b, string $listMode = 'replace'): array
    {
        foreach ($b as $k => $v) {
            if (is_array($v) && isset($a[$k]) && is_array($a[$k])) {
                $left = $a[$k];
                if (array_is_list($left) && array_is_list($v)) {
                    if ($listMode === 'replace') {
                        $a[$k] = $v;
                    } elseif ($listMode === 'append') {
                        $a[$k] = array_merge($left, $v);
                    } else { // append_unique
                        $acc = $left;
                        foreach ($v as $vv) {
                            if (!in_array($vv, $acc, true)) {
                                $acc[] = $vv;
                            }
                        }
                        $a[$k] = $acc;
                    }
                } else {
                    $a[$k] = $this->mergeArrays($left, $v, $listMode);
                }
            } else {
                $a[$k] = $v;
            }
        }

        return $a;
    }

    // --- Приватные хелперы ---

    private function valueOf(mixed $item, string|callable|null $by, string|int|null $key = null): mixed
    {
        if ($by === null) {
            return $item;
        }

        if (is_callable($by)) {
            return $by($item, $key);
        }

        return $this->dataGet($item, $by);
    }

    private function numericValues(string|callable|null $by = null): array
    {
        $values = [];

        foreach ($this->items as $key => $item) {
            $value = $this->valueOf($item, $by, $key);
            if (is_numeric($value)) {
                $values[] = $value + 0;
            }
        }

        return $values;
    }

    private function hashValue(mixed $value, bool $strict): string
    {
        if (is_scalar($value) || $value === null) {
            if ($strict) {
                return gettype($value) . ':' . (string) $value;
            }

            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeWhereArgs(mixed $operator, mixed $value): array
    {
        $operators = ['=', '==', '!=', '<>', '<', '>', '<=', '>=', '===', '!=='];

        if ($operator === null && $value === null) {
            return ['=', null];
        }

        if ($value === null && !in_array($operator, $operators, true)) {
            return ['=', $operator];
        }

        return [$operator, $value];
    }

    private function compare(mixed $left, string $operator, mixed $right): bool
    {
        return match ($operator) {
            '=', '==' => $left == $right,
            '===' => $left === $right,
            '!=', '<>' => $left != $right,
            '!=='   => $left !== $right,
            '>'     => $left > $right,
            '>='    => $left >= $right,
            '<'     => $left < $right,
            '<='    => $left <= $right,
            default => $left == $right,
        };
    }

    private function dataGet(mixed $target, string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $target;
        }

        if (str_contains($key, '*') && is_array($target)) {
            return ArrayHelper::getPath($target, $key, $default);
        }

        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (self::isAccessible($target) && ArrayHelper::exists($target, $segment)) {
                $target = $target[$segment];
                continue;
            }

            if (is_object($target)) {
                if (isset($target->{$segment}) || property_exists($target, (string) $segment)) {
                    $target = $target->{$segment};
                    continue;
                }

                $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $segment)));
                if (method_exists($target, $getter)) {
                    $target = $target->{$getter}();
                    continue;
                }
            }

            return $default;
        }

        return $target;
    }

    private static function isAccessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Извлечь значение по ключу у массива/объекта (поле/геттер)
     */
    private function extract(mixed $item, string $key): mixed
    {
        return $this->dataGet($item, $key);
    }
}
