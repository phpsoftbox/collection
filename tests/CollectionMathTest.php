<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'sum')]
#[CoversMethod(Collection::class, 'average')]
#[CoversMethod(Collection::class, 'avg')]
#[CoversMethod(Collection::class, 'median')]
#[CoversMethod(Collection::class, 'percentile')]
#[CoversMethod(Collection::class, 'percentage')]
#[CoversMethod(Collection::class, 'min')]
#[CoversMethod(Collection::class, 'max')]
final class CollectionMathTest extends TestCase
{
    /**
     * Проверяет sum/average/median на числах.
     */
    #[Test]
    public function testSumAverageMedian(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $this->assertSame(10, $c->sum());
        $this->assertSame(2.5, $c->average());
        $this->assertSame(2.5, $c->avg());
        $this->assertSame(2.5, $c->median());

        $odd = new Collection([1, 5, 9]);

        $this->assertSame(5, $odd->median());
    }

    /**
     * Проверяет sum/average с ключом.
     */
    #[Test]
    public function testSumWithCallback(): void
    {
        $c = new Collection([
            ['score' => 10],
            ['score' => 20],
        ]);

        $this->assertSame(30, $c->sum('score'));
        $this->assertSame(15.0, $c->average('score'));
    }

    /**
     * Проверяет percentile.
     */
    #[Test]
    public function testPercentile(): void
    {
        $c = new Collection([10, 20, 30, 40]);

        $this->assertSame(10, $c->percentile(0));
        $this->assertSame(40, $c->percentile(100));
        $this->assertSame(25.0, $c->percentile(50));
    }

    /**
     * Проверяет percentage/min/max.
     */
    #[Test]
    public function testPercentageMinMax(): void
    {
        $c = new Collection([1, 0, 1, 1]);

        $this->assertSame(75.0, $c->percentage());

        $users = new Collection([
            ['active' => true],
            ['active' => false],
        ]);

        $this->assertSame(50.0, $users->percentage('active'));

        $numbers = new Collection([1, 5, 3]);

        $this->assertSame(1, $numbers->min());
        $this->assertSame(5, $numbers->max());
    }
}
