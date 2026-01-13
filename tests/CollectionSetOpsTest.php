<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'intersect')]
#[CoversMethod(Collection::class, 'diff')]
#[CoversMethod(Collection::class, 'duplicates')]
final class CollectionSetOpsTest extends TestCase
{
    /**
     * Проверяет intersect/diff.
     */
    #[Test]
    public function testIntersectAndDiff(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['b' => 2, 'c' => 3], $c->intersect([2, 3])->all());
        $this->assertSame(['b' => 2, 'c' => 3], $c->intersect(new Collection([2, 3]))->all());
        $this->assertSame(['a' => 1], $c->diff([2, 3])->all());
    }

    /**
     * Проверяет duplicates.
     */
    #[Test]
    public function testDuplicates(): void
    {
        $c = new Collection([1, 2, 1, 1, 3]);

        $this->assertSame([2 => 1, 3 => 1], $c->duplicates()->all());

        $users = new Collection([
            ['id' => 1, 'email' => 'a@test'],
            ['id' => 2, 'email' => 'b@test'],
            ['id' => 3, 'email' => 'a@test'],
        ]);

        $this->assertSame([2 => 'a@test'], $users->duplicates('email')->all());
    }
}
