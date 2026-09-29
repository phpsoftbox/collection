<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\ArrayHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayHelper::class)]
#[CoversMethod(ArrayHelper::class, 'keyBy')]
#[CoversMethod(ArrayHelper::class, 'partition')]
#[CoversMethod(ArrayHelper::class, 'dot')]
#[CoversMethod(ArrayHelper::class, 'undot')]
final class ArrayHelperSelectorTest extends TestCase
{
    /**
     * Проверим, что keyBy('key') индексирует по полю key, а не вызывает функцию key().
     *
     * @see ArrayHelper::keyBy()
     */
    #[Test]
    public function keyByTreatsFunctionNameAsFieldPath(): void
    {
        $items = [['key' => 'x'], ['key' => 'y']];

        $this->assertSame(['x' => ['key' => 'x'], 'y' => ['key' => 'y']], ArrayHelper::keyBy($items, 'key'));
    }

    /**
     * Проверим, что partition('is_null') делит по полю is_null, а не вызывает функцию.
     *
     * @see ArrayHelper::partition()
     */
    #[Test]
    public function partitionTreatsFunctionNameAsFieldPath(): void
    {
        $items = [['is_null' => true], ['is_null' => false]];

        $this->assertSame(
            [[0 => ['is_null' => true]], [1 => ['is_null' => false]]],
            ArrayHelper::partition($items, 'is_null'),
        );
    }

    /**
     * Проверим, что dot() сохраняет пустые массивы как значения.
     *
     * @see ArrayHelper::dot()
     */
    #[Test]
    public function dotKeepsEmptyArrays(): void
    {
        $this->assertSame(
            ['a.b' => [], 'a.c' => 1, 'tags' => []],
            ArrayHelper::dot(['a' => ['b' => [], 'c' => 1], 'tags' => []]),
        );
    }

    /**
     * Проверим, что undot(dot()) восстанавливает структуру с пустыми массивами.
     *
     * @see ArrayHelper::dot()
     * @see ArrayHelper::undot()
     */
    #[Test]
    public function undotRestoresEmptyArrays(): void
    {
        $data = ['a' => ['b' => [], 'c' => 1], 'tags' => []];

        $this->assertSame($data, ArrayHelper::undot(ArrayHelper::dot($data)));
    }
}
