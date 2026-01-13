<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, '__construct')]
#[CoversMethod(Collection::class, 'from')]
#[CoversMethod(Collection::class, 'all')]
#[CoversMethod(Collection::class, 'count')]
#[CoversMethod(Collection::class, 'map')]
#[CoversMethod(Collection::class, 'filter')]
#[CoversMethod(Collection::class, 'reduce')]
#[CoversMethod(Collection::class, 'first')]
#[CoversMethod(Collection::class, 'last')]
#[CoversMethod(Collection::class, 'push')]
#[CoversMethod(Collection::class, 'getIterator')]
#[CoversMethod(Collection::class, 'keys')]
final class CollectionTest extends TestCase
{
    /**
     * Проверяет all() и Countable.
     */
    #[Test]
    public function testAllAndCount(): void
    {
        $arr = new Collection([1, 2, 3]);

        $this->assertSame([1, 2, 3], $arr->all());
        $this->assertCount(3, $arr);
    }

    /**
     * Проверяет map/filter/reduce.
     */
    #[Test]
    public function testMapFilterReduce(): void
    {
        $arr = new Collection([1, 2, 3, 4]);

        $mapped = $arr->map(fn ($x) => $x * 2);
        $this->assertSame([2, 4, 6, 8], $mapped->all());

        $filtered = $arr->filter(fn ($x) => $x % 2 === 0);
        $this->assertSame([2, 4], $filtered->all());

        $sum = $arr->reduce(fn ($carry, $x) => ($carry ?? 0) + $x, 0);
        $this->assertSame(10, $sum);
    }

    /**
     * Проверяет first с предикатом и default.
     */
    #[Test]
    public function testFirst(): void
    {
        $arr = new Collection([10, 20, 30]);

        $this->assertSame(10, $arr->first());
        $this->assertSame(20, $arr->first(fn ($x) => $x > 15));
        $this->assertSame('none', $arr->first(fn ($x) => $x > 100, 'none'));
    }

    /**
     * Проверяет last с предикатом и default.
     */
    #[Test]
    public function testLast(): void
    {
        $arr = new Collection([10, 20, 30]);

        $this->assertSame(30, $arr->last());
        $this->assertSame(20, $arr->last(fn ($x) => $x < 30));
        $this->assertSame('none', $arr->last(fn ($x) => $x > 100, 'none'));
    }

    /**
     * Проверяет push и итератор.
     */
    #[Test]
    public function testPushAndIterator(): void
    {
        $arr = new Collection();

        $arr->push(1, 2)->push(3);
        $this->assertSame([1, 2, 3], iterator_to_array($arr));
    }

    /**
     * Проверяет keys().
     */
    #[Test]
    public function testCollectionKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);

        $this->assertSame(['a', 'b'], $c->keys());
    }
}
