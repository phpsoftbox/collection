<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'filter')]
#[CoversMethod(Collection::class, 'where')]
#[CoversMethod(Collection::class, 'map')]
#[CoversMethod(Collection::class, 'values')]
#[CoversMethod(Collection::class, 'get')]
#[CoversMethod(Collection::class, 'has')]
final class CollectionKeysTest extends TestCase
{
    /**
     * Проверим, что filter() сохраняет строковые ключи ассоциативной коллекции.
     *
     * @see Collection::filter()
     */
    #[Test]
    public function filterPreservesAssociativeKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $c->filter(static fn (int $v): bool => $v !== 2)->all());
    }

    /**
     * Проверим, что where() сохраняет ключи так же, как filter().
     *
     * @see Collection::where()
     */
    #[Test]
    public function wherePreservesKeys(): void
    {
        $c = new Collection([
            'alice' => ['active' => true],
            'bob'   => ['active' => false],
        ]);

        $this->assertSame(['alice' => ['active' => true]], $c->where('active', true)->all());
    }

    /**
     * Проверим, что map() сохраняет ключи.
     *
     * @see Collection::map()
     */
    #[Test]
    public function mapPreservesKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 10, 'b' => 20], $c->map(static fn (int $v): int => $v * 10)->all());
    }

    /**
     * Проверим, что values() после filter() даёт список с плотной нумерацией.
     *
     * @see Collection::filter()
     * @see Collection::values()
     */
    #[Test]
    public function valuesAfterFilterReturnsList(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $this->assertSame([2, 4], $c->filter(static fn (int $v): bool => $v % 2 === 0)->values()->all());
    }

    /**
     * Проверим, что get() возвращает сохранённый null, а не $default.
     *
     * @see Collection::get()
     */
    #[Test]
    public function getReturnsStoredNullInsteadOfDefault(): void
    {
        $c = new Collection(['a' => null]);

        $this->assertNull($c->get('a', 'default'));
    }

    /**
     * Проверим, что get() возвращает $default для отсутствующего ключа, а has() отличает его от null.
     *
     * @see Collection::get()
     * @see Collection::has()
     */
    #[Test]
    public function getReturnsDefaultForMissingKey(): void
    {
        $c = new Collection(['a' => null]);

        $this->assertSame('default', $c->get('missing', 'default'));
        $this->assertTrue($c->has('a'));
        $this->assertFalse($c->has('missing'));
    }
}
