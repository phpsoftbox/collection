<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_values;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'getPath')]
#[CoversMethod(Collection::class, 'hasPath')]
#[CoversMethod(Collection::class, 'setPath')]
#[CoversMethod(Collection::class, 'forget')]
#[CoversMethod(Collection::class, 'unique')]
#[CoversMethod(Collection::class, 'indexBy')]
#[CoversMethod(Collection::class, 'toArray')]
#[CoversMethod(Collection::class, 'values')]
#[CoversMethod(Collection::class, 'resetKeys')]
#[CoversMethod(Collection::class, 'add')]
#[CoversMethod(Collection::class, 'has')]
#[CoversMethod(Collection::class, 'get')]
#[CoversMethod(Collection::class, 'remove')]
#[CoversMethod(Collection::class, 'chunk')]
#[CoversMethod(Collection::class, 'sortByKeys')]
#[CoversMethod(Collection::class, 'sortByValues')]
#[CoversMethod(Collection::class, 'sortBy')]
#[CoversMethod(Collection::class, 'insertAt')]
#[CoversMethod(Collection::class, 'insertBefore')]
#[CoversMethod(Collection::class, 'insertAfter')]
#[CoversMethod(Collection::class, 'only')]
#[CoversMethod(Collection::class, 'pick')]
#[CoversMethod(Collection::class, 'except')]
final class CollectionAdvancedTest extends TestCase
{
    /**
     * Проверяет dot-нотацию get/set/forget/has.
     */
    #[Test]
    public function testDotNotationGetSetForgetHas(): void
    {
        $arr = new Collection(['a' => ['b' => ['c' => 1]]]);

        $this->assertTrue($arr->hasPath('a.b.c'));
        $this->assertSame(1, $arr->getPath('a.b.c'));

        $arr->setPath('a.b.d', 2);
        $this->assertSame(2, $arr->getPath('a.b.d'));

        $arr->forget('a.b.c');
        $this->assertFalse($arr->hasPath('a.b.c'));
    }

    /**
     * Проверяет unique по значениям и по ключу.
     */
    #[Test]
    public function testUnique(): void
    {
        $arr = new Collection([1, 1, 2, 2, 3]);

        $this->assertSame([1,2,3], $arr->unique()->all());

        $arr2 = new Collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 1, 'name' => 'A-dup'],
            ['id' => 2, 'name' => 'B'],
        ]);

        $u = $arr2->unique('id')->all();
        $this->assertCount(2, $u);
        $this->assertSame(1, $u[0]['id']);
        $this->assertSame(2, $u[1]['id']);
    }

    /**
     * Проверяет indexBy.
     */
    #[Test]
    public function testIndexBy(): void
    {
        $arr = new Collection([
            ['id' => 10, 'name' => 'A'],
            ['id' => 20, 'name' => 'B'],
        ]);

        $by = $arr->indexBy('id')->all();
        $this->assertArrayHasKey('10', $by);
        $this->assertArrayHasKey('20', $by);
        $this->assertSame('A', $by['10']['name']);
    }

    /**
     * Проверяет toArray/values/resetKeys.
     */
    #[Test]
    public function testToArrayValuesResetKeys(): void
    {
        $arr = new Collection([10 => 'a', 20 => 'b']);

        $this->assertSame([10 => 'a', 20 => 'b'], $arr->toArray());
        $this->assertSame(['a','b'], $arr->values()->all());
        $this->assertSame(['a','b'], $arr->resetKeys()->all());
    }

    /**
     * Проверяет top-level add/get/has/remove.
     */
    #[Test]
    public function testTopLevelGetAddHasRemove(): void
    {
        $arr = new Collection(['x' => 1]);

        $this->assertTrue($arr->has('x'));
        $this->assertSame(1, $arr->get('x'));
        $this->assertNull($arr->get('y'));
        $this->assertSame('def', $arr->get('y', 'def'));

        $arr->add('y', 2);
        $this->assertTrue($arr->has('y'));
        $this->assertSame(2, $arr->get('y'));

        $arr->remove('x');
        $this->assertFalse($arr->has('x'));
    }

    /**
     * Проверяет chunk.
     */
    #[Test]
    public function testChunk(): void
    {
        $arr = new Collection([1,2,3,4,5]);

        $chunks = $arr->chunk(2)->all();
        $this->assertCount(3, $chunks);
        $this->assertSame([1,2], array_values($chunks[0]));
        $this->assertSame([3,4], array_values($chunks[1]));
        $this->assertSame([5], array_values($chunks[2]));
    }

    /**
     * Проверяет сортировки.
     */
    #[Test]
    public function testSorts(): void
    {
        $arr = new Collection(['b' => 2, 'a' => 1, 'c' => 3]);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr->sortByKeys()->all());
        $this->assertSame(['c' => 3, 'b' => 2, 'a' => 1], $arr->sortByKeys(true)->all());
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr->sortByValues()->all());
        $this->assertSame(['c' => 3, 'b' => 2, 'a' => 1], $arr->sortByValues(true)->all());

        $arr2 = new Collection([
            ['id' => 2, 'name' => 'B'],
            ['id' => 1, 'name' => 'A'],
        ]);

        $res = $arr2->sortBy('id')->all();
        $this->assertSame(1, $res[1]['id']);
        $resDesc = $arr2->sortBy('name', true)->all();
        $this->assertSame('B', $resDesc[0]['name']);
    }

    /**
     * Проверяет вставки insertAt/insertBefore/insertAfter.
     */
    #[Test]
    public function testInsertAtBeforeAfter(): void
    {
        $arr = new Collection(['a' => 1, 'b' => 2]);

        $arr->insertAt(1, 'x', 9);
        $this->assertSame(['a' => 1, 'x' => 9, 'b' => 2], $arr->all());

        $arr->insertBefore('a', 'y', 7);
        $this->assertSame(['y' => 7, 'a' => 1, 'x' => 9, 'b' => 2], $arr->all());

        $arr->insertAfter('b', 'z', 8);
        $this->assertSame(['y' => 7, 'a' => 1, 'x' => 9, 'b' => 2, 'z' => 8], $arr->all());
    }

    /**
     * Проверяет only/pick/except.
     */
    #[Test]
    public function testOnlyPickExcept(): void
    {
        $arr = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertSame(['a' => 1, 'c' => 3], $arr->only(['a','c'])->all());
        $this->assertSame(['a' => 1, 'c' => 3], $arr->pick(['a','c'])->all());
        $this->assertSame(['b' => 2], $arr->except(['a','c'])->all());
    }
}
