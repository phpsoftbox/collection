<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

use function array_chunk;
use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_push;
use function array_reduce;
use function array_replace;
use function array_values;
use function arsort;
use function asort;
use function count;
use function explode;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_object;
use function is_scalar;
use function is_string;
use function json_encode;
use function krsort;
use function ksort;
use function max;
use function method_exists;
use function str_replace;
use function uasort;
use function ucwords;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const SORT_REGULAR;

class Collection implements IteratorAggregate, Countable
{
    /**
     * @param array $items Инициализационный массив
     */
    public function __construct(
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
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Преобразовать в массив (синоним all)
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
     * Получить значение по пути (a.b.c)
     */
    public function getPath(string $path, mixed $default = null): mixed
    {
        $segments        = $this->pathSegments($path);
        [$found, $value] = $this->findPath($segments);

        return $found ? $value : $default;
    }

    /**
     * Проверить наличие пути (a.b.c)
     */
    public function hasPath(string $path): bool
    {
        $segments = $this->pathSegments($path);
        [$found,] = $this->findPath($segments);

        return $found;
    }

    /**
     * Установить значение по пути (a.b.c), создавая вложенные массивы
     * @return $this
     */
    public function setPath(string $path, mixed $value): self
    {
        $segments = $this->pathSegments($path);
        if ($segments === []) {
            return $this; // пустой путь игнорируем
        }
        $ref = &$this->items;
        foreach ($segments as $seg) {
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                $ref[$seg] = [];
            }
            $ref = &$ref[$seg];
        }
        $ref = $value;

        return $this;
    }

    /**
     * Удалить ключ(и) по пути/путям (поддерживает массив путей)
     * @return $this
     */
    public function forget(string|array $paths): self
    {
        foreach ((array) $paths as $path) {
            $segments = $this->pathSegments((string) $path);
            if ($segments === []) {
                continue;
            }
            $ref  = &$this->items;
            $last = array_pop($segments);
            foreach ($segments as $seg) {
                if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                    continue 2;
                }
                $ref = &$ref[$seg];
            }
            unset($ref[$last]);
        }

        return $this;
    }

    /**
     * Вернуть только указанные верхнеуровневые ключи
     */
    public function only(array $keys): self
    {
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $this->items)) {
                $out[$k] = $this->items[$k];
            }
        }

        return new self($out);
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
        $out = $this->items;
        foreach ($keys as $k) {
            unset($out[$k]);
        }

        return new self($out);
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
     * Свёртка элементов
     */
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $fn, $initial);
    }

    /**
     * Первый элемент (по предикату или без него)
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
        $result = [];
        $stack  = function ($array, $prefix) use (&$stack, &$result) {
            foreach ($array as $k => $v) {
                $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
                if (is_array($v)) {
                    $stack($v, $key);
                } else {
                    $result[$key] = $v;
                }
            }
        };
        $stack($this->items, $prepend);

        return new self($result);
    }

    /**
     * Собрать вложенный массив из плоского (dot-keys)
     */
    public static function undot(array $flat): self
    {
        $out = [];
        foreach ($flat as $path => $value) {
            $segments = explode('.', (string) $path);
            $ref      = &$out;
            foreach ($segments as $seg) {
                if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                    $ref[$seg] = [];
                }
                $ref = &$ref[$seg];
            }
            $ref = $value;
        }

        return new self($out);
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

    /**
     * Разбить путь по точке в массив сегментов
     */
    private function pathSegments(string $path): array
    {
        return $path === '' ? [] : explode('.', $path);
    }

    /**
     * Найти значение по сегментам пути
     * @return array [bool found, mixed value]
     */
    private function findPath(array $segments): array
    {
        $value = $this->items;
        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) {
                return [false, null];
            }
            $value = $value[$seg];
        }

        return [true, $value];
    }

    /**
     * Извлечь значение по ключу у массива/объекта (поле/геттер)
     */
    private function extract(mixed $item, string $key): mixed
    {
        if (is_array($item) && array_key_exists($key, $item)) {
            return $item[$key];
        }
        if (is_object($item)) {
            if (isset($item->{$key})) {
                return $item->{$key};
            }
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));
            if (method_exists($item, $getter)) {
                return $item->{$getter}();
            }
        }

        return null;
    }
}
