<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'dot')]
#[CoversMethod(Collection::class, 'undot')]
#[CoversMethod(Collection::class, 'chunk')]
#[CoversMethod(Collection::class, 'insertBefore')]
#[CoversMethod(Collection::class, 'insertAt')]
#[CoversMethod(Collection::class, 'unique')]
#[CoversMethod(Collection::class, 'indexBy')]
#[CoversMethod(Collection::class, 'setPath')]
#[CoversMethod(Collection::class, 'forget')]
final class CollectionDotUndotEdgeTest extends TestCase
{
    /**
     * Проверяет dot с префиксом и обратную операцию undot.
     *
     * @see Collection::dot()
     * @see Collection::undot()
     */
    #[Test]
    public function dotAndUndotWithPrefix(): void
    {
        $c = new Collection(['a' => ['b' => ['c' => 1]]]);

        $flat = $c->dot('root')->all();
        $this->assertArrayHasKey('root.a.b.c', $flat);
        $orig = Collection::undot($flat)->all();
        $this->assertSame(['root' => ['a' => ['b' => ['c' => 1]]]], $orig);
    }

    /**
     * Проверяет chunk<1 и вставку insertBefore/insertAt для крайних случаев.
     *
     * @see Collection::chunk()
     * @see Collection::insertBefore()
     * @see Collection::insertAt()
     */
    #[Test]
    public function chunkAndInsertEdges(): void
    {
        $c = new Collection([1,2,3]);

        $chunks = $c->chunk(0)->all();
        $this->assertCount(1, $chunks);
        $this->assertSame([1,2,3], $chunks[0]);

        $d = new Collection(['a' => 1]);

        $d->insertBefore('missing', 'x', 9);
        $this->assertSame(['a' => 1, 'x' => 9], $d->all());

        $e = new Collection(['a' => 1]);

        $e->insertAt(10, 'y', 7);
        $this->assertSame(['a' => 1, 'y' => 7], $e->all());
    }

    /**
     * Проверяет unique/indexBy с callable и поведение setPath/forget с пустым путём.
     *
     * @see Collection::unique()
     * @see Collection::indexBy()
     * @see Collection::setPath()
     * @see Collection::forget()
     */
    #[Test]
    public function uniqueAndIndexByCallableAndEmptyPaths(): void
    {
        $arr = new Collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 1, 'name' => 'A-dup'],
            ['id' => 2, 'name' => 'B'],
        ]);

        $u = $arr->unique(fn ($x) => $x['id'])->all();
        $this->assertCount(2, $u);

        $by = $arr->indexBy(fn ($x) => 'k' . $x['id'])->all();
        $this->assertArrayHasKey('k1', $by);
        $this->assertArrayHasKey('k2', $by);

        $c = new Collection(['a' => 1]);

        $c->setPath('', 2); // игнор
        $c->forget(['']);   // игнор
        $this->assertSame(['a' => 1], $c->all());
    }
}
