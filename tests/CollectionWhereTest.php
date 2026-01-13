<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'where')]
#[CoversMethod(Collection::class, 'whereStrict')]
#[CoversMethod(Collection::class, 'whereIn')]
#[CoversMethod(Collection::class, 'whereNotIn')]
#[CoversMethod(Collection::class, 'whereBetween')]
#[CoversMethod(Collection::class, 'whereNotBetween')]
#[CoversMethod(Collection::class, 'whereNull')]
#[CoversMethod(Collection::class, 'whereNotNull')]
final class CollectionWhereTest extends TestCase
{
    /**
     * Проверяет where и whereStrict.
     */
    #[Test]
    public function testWhereAndWhereStrict(): void
    {
        $c = new Collection([
            ['id' => 1, 'score' => 10],
            ['id' => 2, 'score' => '10'],
        ]);

        $this->assertSame([['id' => 1, 'score' => 10], ['id' => 2, 'score' => '10']], $c->where('score', 10)->all());
        $this->assertSame([['id' => 1, 'score' => 10]], $c->whereStrict('score', 10)->all());
    }

    /**
     * Проверяет where с операторами.
     */
    #[Test]
    public function testWhereOperators(): void
    {
        $c = new Collection([
            ['id' => 1, 'score' => 5],
            ['id' => 2, 'score' => 15],
        ]);

        $this->assertSame([['id' => 2, 'score' => 15]], $c->where('score', '>', 10)->all());
        $this->assertSame([['id' => 1, 'score' => 5]], $c->where('score', '<', 10)->all());
    }

    /**
     * Проверяет whereIn/whereNotIn/whereBetween/whereNull.
     */
    #[Test]
    public function testWhereInNotInBetweenNull(): void
    {
        $c = new Collection([
            ['id' => 1, 'score' => 5, 'meta' => ['rank' => 1]],
            ['id' => 2, 'score' => 15, 'meta' => ['rank' => null]],
            ['id' => 3, 'score' => 25, 'meta' => ['rank' => 3]],
        ]);

        $this->assertSame(
            [
                ['id' => 1, 'score' => 5, 'meta' => ['rank' => 1]],
                ['id' => 3, 'score' => 25, 'meta' => ['rank' => 3]],
            ],
            $c->whereIn('id', [1, 3])->all(),
        );

        $this->assertSame(
            [
                ['id' => 2, 'score' => 15, 'meta' => ['rank' => null]],
            ],
            $c->whereNotIn('id', [1, 3])->all(),
        );

        $this->assertSame(
            [
                ['id' => 2, 'score' => 15, 'meta' => ['rank' => null]],
            ],
            $c->whereBetween('score', [10, 20])->all(),
        );

        $this->assertSame(
            [
                ['id' => 1, 'score' => 5, 'meta' => ['rank' => 1]],
                ['id' => 3, 'score' => 25, 'meta' => ['rank' => 3]],
            ],
            $c->whereNotBetween('score', [10, 20])->all(),
        );

        $this->assertSame(
            [
                ['id' => 2, 'score' => 15, 'meta' => ['rank' => null]],
            ],
            $c->whereNull('meta.rank')->all(),
        );

        $this->assertSame(
            [
                ['id' => 1, 'score' => 5, 'meta' => ['rank' => 1]],
                ['id' => 3, 'score' => 25, 'meta' => ['rank' => 3]],
            ],
            $c->whereNotNull('meta.rank')->all(),
        );
    }
}
