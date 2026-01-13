<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection;

use function array_key_exists;
use function explode;
use function is_array;
use function preg_match;
use function preg_quote;
use function str_contains;
use function str_replace;

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
            $values = [];
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
     * @param string|array $paths
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

    /**
     * Возвращает все совпадения по пути с wildcard.
     * @param array<string, mixed> $items
     * @return list<array{path: string, value: mixed, present: bool}>
     */
    public static function path(array $items, string $pattern): array
    {
        $segments = self::pathSegments($pattern);
        $results = [];

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
