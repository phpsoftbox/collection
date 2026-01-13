<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Traversable;

use function array_any;
use function array_filter;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_rand;
use function array_reverse;
use function array_slice;
use function array_unshift;
use function array_values;
use function arsort;
use function asort;
use function count;
use function explode;
use function filter_var;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_float;
use function is_int;
use function is_iterable;
use function is_numeric;
use function is_object;
use function is_scalar;
use function is_string;
use function iterator_to_array;
use function ksort;
use function method_exists;
use function mt_srand;
use function preg_match;
use function preg_quote;
use function property_exists;
use function rtrim;
use function shuffle;
use function sort;
use function str_contains;
use function str_replace;
use function trim;

use const ARRAY_FILTER_USE_BOTH;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const INF;
use const PHP_QUERY_RFC3986;

final class ArrayHelper
{
    /**
     * @param array<string|int, mixed> $items
     * @param array<int, string|int> $keys
     * @return array<string|int, mixed>
     */
    public static function only(array $items, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $items)) {
                $out[$key] = $items[$key];
            }
        }

        return $out;
    }

    /**
     * @param array<string|int, mixed> $items
     * @param array<int, string|int> $keys
     * @return array<string|int, mixed>
     */
    public static function except(array $items, array $keys): array
    {
        foreach ($keys as $key) {
            unset($items[$key]);
        }

        return $items;
    }

    /**
     * Поддерживает wildcard: возвращает список значений либо default.
     * @param array<string, mixed> $items
     */
    public static function getPath(array $items, string $path, mixed $default = null): mixed
    {
        if (str_contains($path, '*')) {
            $matches = self::path($items, $path);
            $values  = [];
            foreach ($matches as $match) {
                if ($match['present']) {
                    $values[] = $match['value'];
                }
            }

            return $values === [] ? $default : $values;
        }

        $segments        = self::pathSegments($path);
        [$found, $value] = self::findPath($items, $segments);

        return $found ? $value : $default;
    }

    /**
     * Поддерживает wildcard: true, если есть хотя бы одно совпадение.
     * @param array<string, mixed> $items
     */
    public static function hasPath(array $items, string $path): bool
    {
        if (str_contains($path, '*')) {
            $matches = self::path($items, $path);
            foreach ($matches as $match) {
                if ($match['present']) {
                    return true;
                }
            }

            return false;
        }

        $segments = self::pathSegments($path);
        [$found,] = self::findPath($items, $segments);

        return $found;
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    public static function setPath(array $items, string $path, mixed $value): array
    {
        $segments = self::pathSegments($path);
        if ($segments === []) {
            return $items;
        }

        $ref = &$items;
        foreach ($segments as $seg) {
            if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                $ref[$seg] = [];
            }
            $ref = &$ref[$seg];
        }
        $ref = $value;

        return $items;
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    public static function forget(array $items, string|array $paths): array
    {
        foreach ((array) $paths as $path) {
            $segments = self::pathSegments((string) $path);
            if ($segments === []) {
                continue;
            }
            $ref  = &$items;
            $last = array_pop($segments);
            foreach ($segments as $seg) {
                if (!isset($ref[$seg]) || !is_array($ref[$seg])) {
                    continue 2;
                }
                $ref = &$ref[$seg];
            }
            unset($ref[$last]);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    public static function dot(array $items, string $prepend = ''): array
    {
        $result = [];
        $stack  = function ($array, $prefix) use (&$stack, &$result): void {
            foreach ($array as $k => $v) {
                $key = $prefix === '' ? (string) $k : $prefix . '.' . $k;
                if (is_array($v)) {
                    $stack($v, $key);
                } else {
                    $result[$key] = $v;
                }
            }
        };
        $stack($items, $prepend);

        return $result;
    }

    /**
     * @param array<string, mixed> $flat
     * @return array<string, mixed>
     */
    public static function undot(array $flat): array
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

        return $out;
    }

    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function add(array $array, string|int $key, mixed $value): array
    {
        if (!self::has($array, $key)) {
            return self::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function array(array $array, string|int|null $key, array $default = []): array
    {
        $value = self::get($array, $key, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function boolean(array $array, string|int|null $key, bool $default = false): bool
    {
        $value = self::get($array, $key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($filtered !== null) {
                return $filtered;
            }
        }

        return (bool) $value;
    }

    /**
     * @param array<int, array<mixed>> $array
     */
    public static function collapse(array $array): array
    {
        $results = [];
        foreach ($array as $values) {
            if (is_array($values)) {
                $results = array_merge($results, $values);
            }
        }

        return $results;
    }

    public static function crossJoin(array ...$arrays): array
    {
        if ($arrays === []) {
            return [];
        }

        $results = [[]];
        foreach ($arrays as $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $append[] = array_merge($product, [$item]);
                }
            }
            $results = $append;
        }

        return $results;
    }

    /**
     * @param array<string|int, mixed> $array
     * @return array{0: list<string|int>, 1: list<mixed>}
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    public static function every(array $array, ?callable $callback = null): bool
    {
        if ($callback === null) {
            foreach ($array as $value) {
                if (!$value) {
                    return false;
                }
            }

            return true;
        }

        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function exists(array|ArrayAccess $array, string|int $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            foreach ($array as $item) {
                return $item;
            }

            return self::value($default);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return self::value($default);
    }

    public static function flatten(array $array, int|float $depth = INF): array
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
                continue;
            }

            if ($depth === 1) {
                $result = array_merge($result, array_values($item));
                continue;
            }

            $result = array_merge($result, self::flatten($item, $depth - 1));
        }

        return $result;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function float(array $array, string|int|null $key, float $default = 0.0): float
    {
        $value = self::get($array, $key, $default);

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value) || is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     */
    public static function from(mixed $items): array
    {
        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        if (is_array($items)) {
            return $items;
        }

        if ($items === null) {
            return [];
        }

        return (array) $items;
    }

    public static function get(array|ArrayAccess $array, string|int|array|null $key, mixed $default = null): mixed
    {
        return self::dataGet($array, $key, $default);
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function has(array $array, string|array $keys): bool
    {
        $keys = (array) $keys;

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (is_string($key) && str_contains($key, '*')) {
                if (!self::hasPath($array, $key)) {
                    return false;
                }
                continue;
            }

            $segments = explode('.', (string) $key);
            $sub      = $array;
            foreach ($segments as $segment) {
                if (self::accessible($sub) && self::exists($sub, $segment)) {
                    $sub = $sub[$segment];
                    continue;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function hasAll(array $array, string|array $keys): bool
    {
        return self::has($array, $keys);
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function hasAny(array $array, string|array $keys): bool
    {
        return array_any((array) $keys, fn ($key) => self::has($array, $key));
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function integer(array $array, string|int|null $key, int $default = 0): int
    {
        $value = self::get($array, $key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public static function isAssoc(array $array): bool
    {
        return !array_is_list($array);
    }

    public static function isList(array $array): bool
    {
        return array_is_list($array);
    }

    public static function join(array $array, string $glue, string $finalGlue = ''): string
    {
        $count = count($array);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return (string) array_values($array)[0];
        }

        $finalGlue = $finalGlue === '' ? $glue : $finalGlue;
        $last      = array_pop($array);

        return implode($glue, $array) . $finalGlue . $last;
    }

    public static function keyBy(array $array, callable|string $keyBy): array
    {
        $result = [];

        foreach ($array as $item) {
            $key                   = is_callable($keyBy) ? $keyBy($item) : self::dataGet($item, $keyBy);
            $result[(string) $key] = $item;
        }

        return $result;
    }

    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            foreach (array_reverse($array, true) as $item) {
                return $item;
            }

            return self::value($default);
        }

        foreach (array_reverse($array, true) as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return self::value($default);
    }

    public static function map(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return $result;
    }

    public static function mapSpread(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $args = $value;
            } elseif (is_iterable($value)) {
                $args = iterator_to_array($value);
            } else {
                $args = [$value];
            }

            $result[$key] = $callback(...array_values($args));
        }

        return $result;
    }

    public static function mapWithKeys(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $pair = $callback($value, $key);
            if (!is_array($pair)) {
                continue;
            }

            foreach ($pair as $pairKey => $pairValue) {
                $result[$pairKey] = $pairValue;
            }
        }

        return $result;
    }

    public static function partition(array $array, callable|string $callback): array
    {
        $passed = [];
        $failed = [];

        foreach ($array as $key => $value) {
            $result = is_callable($callback) ? $callback($value, $key) : self::dataGet($value, $callback);

            if ($result) {
                $passed[$key] = $value;
            } else {
                $failed[$key] = $value;
            }
        }

        return [$passed, $failed];
    }

    public static function pluck(array $array, string|int $value, string|int|null $key = null): array
    {
        $results = [];

        foreach ($array as $item) {
            $itemValue = self::dataGet($item, $value);

            if ($key === null) {
                $results[] = $itemValue;
                continue;
            }

            $itemKey                    = self::dataGet($item, $key);
            $results[(string) $itemKey] = $itemValue;
        }

        return $results;
    }

    public static function prepend(array $array, mixed $value, string|int|null $key = null): array
    {
        if ($key === null) {
            array_unshift($array, $value);

            return $array;
        }

        return [$key => $value] + $array;
    }

    public static function prependKeysWith(array $array, string $prependWith): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[$prependWith . $key] = $value;
        }

        return $result;
    }

    public static function pull(array &$array, string|int|null $key, mixed $default = null): mixed
    {
        $value = self::get($array, $key, $default);

        if ($key !== null) {
            $array = self::forget($array, (string) $key);
        }

        return $value;
    }

    public static function push(array $array, mixed $value, string|int|null $key = null): array
    {
        if ($key === null) {
            $array[] = $value;

            return $array;
        }

        $current = self::get($array, $key, []);
        if (!is_array($current)) {
            $current = [$current];
        }
        $current[] = $value;

        return self::set($array, $key, $current);
    }

    public static function query(array $array): string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    public static function random(array $array, ?int $number = null, bool $preserveKeys = false): mixed
    {
        $count = count($array);

        if ($count === 0) {
            throw new InvalidArgumentException('Cannot get random value from empty array.');
        }

        if ($number === null) {
            return $array[array_rand($array)];
        }

        if ($number < 0 || $number > $count) {
            throw new InvalidArgumentException('Requested number of items exceeds array size.');
        }

        if ($number === 0) {
            return [];
        }

        $keys = array_rand($array, $number);
        $keys = is_array($keys) ? $keys : [$keys];

        $results = [];
        foreach ($keys as $key) {
            if ($preserveKeys) {
                $results[$key] = $array[$key];
            } else {
                $results[] = $array[$key];
            }
        }

        return $results;
    }

    public static function reject(array $array, callable|bool $callback = true): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $reject = is_callable($callback) ? $callback($value, $key) : (bool) $value;
            if (!$reject) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function select(array $array, array|string $keys): array
    {
        $keys    = (array) $keys;
        $result  = [];
        $missing = new stdClass();

        foreach ($array as $itemKey => $item) {
            $selected = [];
            foreach ($keys as $key) {
                $value = self::dataGet($item, $key, $missing);
                if ($value !== $missing) {
                    $selected[$key] = $value;
                }
            }
            $result[$itemKey] = $selected;
        }

        return $result;
    }

    public static function set(array $array, string|int|null $key, mixed $value): array
    {
        if ($key === null) {
            return $array;
        }

        return self::setPath($array, (string) $key, $value);
    }

    public static function shuffle(array $array, ?int $seed = null): array
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        shuffle($array);

        if ($seed !== null) {
            mt_srand();
        }

        return $array;
    }

    public static function sole(array $array, ?callable $callback = null): mixed
    {
        $items = $callback === null ? $array : self::where($array, $callback);
        $count = count($items);

        if ($count === 1) {
            return array_values($items)[0];
        }

        if ($count === 0) {
            throw new RuntimeException('Array contains no items.');
        }

        throw new RuntimeException('Array contains more than one item.');
    }

    public static function some(array $array, ?callable $callback = null): bool
    {
        if ($callback === null) {
            foreach ($array as $value) {
                if ($value) {
                    return true;
                }
            }

            return false;
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    public static function sort(array $array, ?callable $callback = null): array
    {
        if ($callback === null) {
            asort($array);

            return $array;
        }

        $results = [];
        foreach ($array as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        asort($results);

        $sorted = [];
        foreach (array_keys($results) as $key) {
            $sorted[$key] = $array[$key];
        }

        return $sorted;
    }

    public static function sortDesc(array $array, ?callable $callback = null): array
    {
        if ($callback === null) {
            arsort($array);

            return $array;
        }

        $results = [];
        foreach ($array as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        arsort($results);

        $sorted = [];
        foreach (array_keys($results) as $key) {
            $sorted[$key] = $array[$key];
        }

        return $sorted;
    }

    public static function sortRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sortRecursive($value);
            }
        }

        if (self::isAssoc($array)) {
            ksort($array);

            return $array;
        }

        sort($array);

        return $array;
    }

    /**
     * @param array<string|int, mixed> $array
     */
    public static function string(array $array, string|int|null $key, string $default = ''): string
    {
        $value = self::get($array, $key, $default);

        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $default;
    }

    public static function take(array $array, int $limit): array
    {
        if ($limit < 0) {
            return array_slice($array, $limit, null, true);
        }

        return array_slice($array, 0, $limit, true);
    }

    public static function toCssClasses(array|string $classes): string
    {
        if (is_string($classes)) {
            return $classes;
        }

        $result = [];
        foreach ($classes as $key => $value) {
            if (is_int($key)) {
                if ($value !== null && $value !== '') {
                    $result[] = (string) $value;
                }
                continue;
            }

            if ($value) {
                $result[] = (string) $key;
            }
        }

        return implode(' ', array_values(array_filter($result, fn ($value) => $value !== '')));
    }

    public static function toCssStyles(array|string $styles): string
    {
        if (is_string($styles)) {
            return rtrim($styles, ';');
        }

        $result = [];
        foreach ($styles as $key => $value) {
            if (is_int($key)) {
                $style = trim((string) $value);
                if ($style !== '') {
                    $result[] = rtrim($style, ';');
                }
                continue;
            }

            if ($value === null || $value === false) {
                continue;
            }

            $result[] = $key . ': ' . $value;
        }

        return $result === [] ? '' : implode('; ', $result) . ';';
    }

    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function whereNotNull(array $array): array
    {
        return array_filter($array, static fn ($value) => $value !== null);
    }

    public static function wrap(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Возвращает все совпадения по пути с wildcard.
     * @param array<string, mixed> $items
     * @return list<array{path: string, value: mixed, present: bool}>
     */
    public static function path(array $items, string $pattern): array
    {
        $segments = self::pathSegments($pattern);
        $results  = [];

        self::walkPath($items, $segments, 0, '', $results);

        if ($results === []) {
            return [['path' => $pattern, 'value' => null, 'present' => false]];
        }

        return $results;
    }

    public static function pathMatches(string $pattern, string $path): bool
    {
        if ($pattern === $path) {
            return true;
        }

        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\\*', '[^.]+', $regex);

        return preg_match('/^' . $regex . '$/', $path) === 1;
    }

    private static function value(mixed $value): mixed
    {
        if ($value instanceof Closure) {
            return $value();
        }

        return $value;
    }

    private static function dataGet(mixed $target, string|int|array|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        $segments = is_array($key) ? $key : explode('.', (string) $key);

        foreach ($segments as $index => $segment) {
            if ($segment === '*') {
                if (!is_iterable($target)) {
                    return self::value($default);
                }

                $result = [];
                foreach ($target as $item) {
                    $result[] = self::dataGet($item, array_slice($segments, $index + 1), $default);
                }

                if (in_array('*', array_slice($segments, $index + 1), true)) {
                    return self::collapse($result);
                }

                return $result;
            }

            if (self::accessible($target) && self::exists($target, $segment)) {
                $target = $target[$segment];
                continue;
            }

            if (is_object($target) && (isset($target->{$segment}) || property_exists($target, (string) $segment))) {
                $target = $target->{$segment};
                continue;
            }

            return self::value($default);
        }

        return $target;
    }

    /**
     * @return array{0: bool, 1: mixed}
     */
    private static function findPath(array $items, array $segments): array
    {
        $ref = $items;
        foreach ($segments as $seg) {
            if (is_array($ref) && array_key_exists($seg, $ref)) {
                $ref = $ref[$seg];
            } else {
                return [false, null];
            }
        }

        return [true, $ref];
    }

    /**
     * @return list<string>
     */
    private static function pathSegments(string $path): array
    {
        $path = trim($path);
        if ($path === '') {
            return [];
        }

        return explode('.', $path);
    }

    /**
     * Обходит массив по сегментам пути и собирает совпадения.
     * @param array<string, mixed> $items
     * @param list<string> $segments
     * @param list<array{path: string, value: mixed, present: bool}> $results
     */
    private static function walkPath(array $items, array $segments, int $index, string $prefix, array &$results): void
    {
        if ($index >= count($segments)) {
            $results[] = ['path' => $prefix, 'value' => $items, 'present' => true];

            return;
        }

        $segment = $segments[$index];

        if ($segment === '*') {
            foreach ($items as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                if ($index + 1 >= count($segments)) {
                    $results[] = ['path' => $path, 'value' => $value, 'present' => true];
                    continue;
                }
                if (is_array($value)) {
                    self::walkPath($value, $segments, $index + 1, $path, $results);
                }
            }

            return;
        }

        if (!array_key_exists($segment, $items)) {
            return;
        }

        $next = $items[$segment];
        $path = $prefix === '' ? $segment : $prefix . '.' . $segment;

        if (is_array($next)) {
            self::walkPath($next, $segments, $index + 1, $path, $results);

            return;
        }

        if ($index + 1 >= count($segments)) {
            $results[] = ['path' => $path, 'value' => $next, 'present' => true];
        }
    }
}
