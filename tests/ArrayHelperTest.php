<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\ArrayHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayHelper::class)]
#[CoversMethod(ArrayHelper::class, 'path')]
#[CoversMethod(ArrayHelper::class, 'getPath')]
#[CoversMethod(ArrayHelper::class, 'hasPath')]
final class ArrayHelperTest extends TestCase
{
    /**
     * Проверяет, что path поддерживает wildcard и возвращает все совпадения.
     */
    #[Test]
    public function pathSupportsWildcard(): void
    {
        $data = [
            'items' => [
                ['id' => 10, 'name' => 'A'],
                ['id' => 20, 'name' => 'B'],
            ],
        ];

        $result = ArrayHelper::path($data, 'items.*.id');

        self::assertSame([
            ['path' => 'items.0.id', 'value' => 10, 'present' => true],
            ['path' => 'items.1.id', 'value' => 20, 'present' => true],
        ], $result);
    }

    /**
     * Проверяет, что path возвращает present=false, если совпадений нет.
     */
    #[Test]
    public function pathReturnsAbsentWhenMissing(): void
    {
        $data = [
            'items' => [
                ['id' => 10],
            ],
        ];

        $result = ArrayHelper::path($data, 'missing.key');

        self::assertSame([
            ['path' => 'missing.key', 'value' => null, 'present' => false],
        ], $result);
    }

    /**
     * Проверяет, что getPath поддерживает wildcard и возвращает список значений.
     */
    #[Test]
    public function getPathSupportsWildcard(): void
    {
        $data = [
            'items' => [
                ['id' => 10],
                ['id' => 20],
            ],
        ];

        $result = ArrayHelper::getPath($data, 'items.*.id');

        self::assertSame([10, 20], $result);
    }

    /**
     * Проверяет, что hasPath учитывает wildcard.
     */
    #[Test]
    public function hasPathSupportsWildcard(): void
    {
        $data = [
            'items' => [
                ['id' => 10],
            ],
        ];

        self::assertTrue(ArrayHelper::hasPath($data, 'items.*.id'));
        self::assertFalse(ArrayHelper::hasPath($data, 'items.*.missing'));
    }
}
