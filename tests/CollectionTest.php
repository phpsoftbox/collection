<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

final class CollectionTest extends TestCase
{
    public function testAllAndCount(): void
    {
        $arr = new Collection([1, 2, 3]);

        $this->assertSame([1, 2, 3], $arr->all());
        $this->assertCount(3, $arr);
    }

    public function testMapFilterReduce(): void
    {
        $arr = new Collection([1, 2, 3, 4]);

        $mapped = $arr->map(fn($x) => $x * 2);
        $this->assertSame([2, 4, 6, 8], $mapped->all());

        $filtered = $arr->filter(fn($x) => $x % 2 === 0);
        $this->assertSame([2, 4], $filtered->all());

        $sum = $arr->reduce(fn($carry, $x) => ($carry ?? 0) + $x, 0);
        $this->assertSame(10, $sum);
    }

    public function testFirst(): void
    {
        $arr = new Collection([10, 20, 30]);

        $this->assertSame(10, $arr->first());
        $this->assertSame(20, $arr->first(fn($x) => $x > 15));
        $this->assertSame('none', $arr->first(fn($x) => $x > 100, 'none'));
    }

    public function testPushAndIterator(): void
    {
        $arr = new Collection();

        $arr->push(1, 2)->push(3);
        $this->assertSame([1, 2, 3], iterator_to_array($arr));
    }

    public function testCollectionKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);

        $this->assertSame(['a', 'b'], $c->keys());
    }
}
