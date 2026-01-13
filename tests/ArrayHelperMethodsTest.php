<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use ArrayObject;
use PhpSoftBox\Collection\ArrayHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_values;
use function sort;

#[CoversClass(ArrayHelper::class)]
#[CoversMethod(ArrayHelper::class, 'only')]
#[CoversMethod(ArrayHelper::class, 'except')]
#[CoversMethod(ArrayHelper::class, 'add')]
#[CoversMethod(ArrayHelper::class, 'accessible')]
#[CoversMethod(ArrayHelper::class, 'exists')]
#[CoversMethod(ArrayHelper::class, 'get')]
#[CoversMethod(ArrayHelper::class, 'has')]
#[CoversMethod(ArrayHelper::class, 'set')]
#[CoversMethod(ArrayHelper::class, 'setPath')]
#[CoversMethod(ArrayHelper::class, 'forget')]
#[CoversMethod(ArrayHelper::class, 'dot')]
#[CoversMethod(ArrayHelper::class, 'undot')]
#[CoversMethod(ArrayHelper::class, 'path')]
#[CoversMethod(ArrayHelper::class, 'pathMatches')]
#[CoversMethod(ArrayHelper::class, 'array')]
#[CoversMethod(ArrayHelper::class, 'boolean')]
#[CoversMethod(ArrayHelper::class, 'integer')]
#[CoversMethod(ArrayHelper::class, 'float')]
#[CoversMethod(ArrayHelper::class, 'string')]
#[CoversMethod(ArrayHelper::class, 'collapse')]
#[CoversMethod(ArrayHelper::class, 'crossJoin')]
#[CoversMethod(ArrayHelper::class, 'divide')]
#[CoversMethod(ArrayHelper::class, 'flatten')]
#[CoversMethod(ArrayHelper::class, 'take')]
#[CoversMethod(ArrayHelper::class, 'wrap')]
#[CoversMethod(ArrayHelper::class, 'from')]
#[CoversMethod(ArrayHelper::class, 'every')]
#[CoversMethod(ArrayHelper::class, 'some')]
#[CoversMethod(ArrayHelper::class, 'first')]
#[CoversMethod(ArrayHelper::class, 'last')]
#[CoversMethod(ArrayHelper::class, 'hasAll')]
#[CoversMethod(ArrayHelper::class, 'hasAny')]
#[CoversMethod(ArrayHelper::class, 'isAssoc')]
#[CoversMethod(ArrayHelper::class, 'isList')]
#[CoversMethod(ArrayHelper::class, 'join')]
#[CoversMethod(ArrayHelper::class, 'keyBy')]
#[CoversMethod(ArrayHelper::class, 'pluck')]
#[CoversMethod(ArrayHelper::class, 'select')]
#[CoversMethod(ArrayHelper::class, 'map')]
#[CoversMethod(ArrayHelper::class, 'mapSpread')]
#[CoversMethod(ArrayHelper::class, 'mapWithKeys')]
#[CoversMethod(ArrayHelper::class, 'partition')]
#[CoversMethod(ArrayHelper::class, 'prepend')]
#[CoversMethod(ArrayHelper::class, 'prependKeysWith')]
#[CoversMethod(ArrayHelper::class, 'push')]
#[CoversMethod(ArrayHelper::class, 'pull')]
#[CoversMethod(ArrayHelper::class, 'query')]
#[CoversMethod(ArrayHelper::class, 'random')]
#[CoversMethod(ArrayHelper::class, 'shuffle')]
#[CoversMethod(ArrayHelper::class, 'reject')]
#[CoversMethod(ArrayHelper::class, 'where')]
#[CoversMethod(ArrayHelper::class, 'whereNotNull')]
#[CoversMethod(ArrayHelper::class, 'sort')]
#[CoversMethod(ArrayHelper::class, 'sortDesc')]
#[CoversMethod(ArrayHelper::class, 'sortRecursive')]
#[CoversMethod(ArrayHelper::class, 'sole')]
#[CoversMethod(ArrayHelper::class, 'toCssClasses')]
#[CoversMethod(ArrayHelper::class, 'toCssStyles')]
final class ArrayHelperMethodsTest extends TestCase
{
    /**
     * Проверяет базовые операции only/except/add и проверку доступности.
     */
    #[Test]
    public function testOnlyExceptAddAccessibleExists(): void
    {
        $items = ['a' => 1, 'b' => 2];

        $this->assertSame(['a' => 1], ArrayHelper::only($items, ['a']));
        $this->assertSame(['b' => 2], ArrayHelper::except($items, ['a']));

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], ArrayHelper::add($items, 'c', 3));
        $this->assertSame(['a' => 1, 'b' => 2], ArrayHelper::add($items, 'a', 9));

        $this->assertTrue(ArrayHelper::accessible($items));
        $this->assertTrue(ArrayHelper::exists(new ArrayObject(['x' => 1]), 'x'));
        $this->assertFalse(ArrayHelper::accessible('nope'));
    }

    /**
     * Проверяет get/set/has/forget и dot-операции.
     */
    #[Test]
    public function testGetSetHasForgetDotUndotPath(): void
    {
        $data = ['a' => ['b' => ['c' => 1]]];

        $this->assertSame(1, ArrayHelper::get($data, 'a.b.c'));
        $this->assertTrue(ArrayHelper::has($data, 'a.b.c'));

        $data = ArrayHelper::set($data, 'a.b.d', 2);
        $this->assertSame(2, ArrayHelper::get($data, 'a.b.d'));

        $data = ArrayHelper::setPath($data, 'a.b.e', 3);
        $this->assertSame(3, ArrayHelper::getPath($data, 'a.b.e'));

        $data = ArrayHelper::forget($data, 'a.b.c');
        $this->assertFalse(ArrayHelper::has($data, 'a.b.c'));

        $flat = ArrayHelper::dot(['x' => ['y' => 5]]);
        $this->assertSame(['x.y' => 5], $flat);
        $this->assertSame(['x' => ['y' => 5]], ArrayHelper::undot($flat));

        $this->assertTrue(ArrayHelper::pathMatches('items.*.id', 'items.10.id'));
        $paths = ArrayHelper::path(['items' => [['id' => 1]]], 'items.*.id');
        $this->assertSame(1, $paths[0]['value']);
    }

    /**
     * Проверяет типизированные getters.
     */
    #[Test]
    public function testArrayBooleanIntegerFloatString(): void
    {
        $data = ['flag' => 'false', 'count' => '10', 'price' => '9.5', 'name' => 123];

        $this->assertSame([], ArrayHelper::array($data, 'missing'));
        $this->assertSame(false, ArrayHelper::boolean($data, 'flag'));
        $this->assertSame(10, ArrayHelper::integer($data, 'count'));
        $this->assertSame(9.5, ArrayHelper::float($data, 'price'));
        $this->assertSame('123', ArrayHelper::string($data, 'name'));
    }

    /**
     * Проверяет collapse/crossJoin/divide/flatten/take/wrap.
     */
    #[Test]
    public function testCollapseCrossJoinDivideFlattenTakeWrap(): void
    {
        $this->assertSame([1, 2, 3], ArrayHelper::collapse([[1, 2], [3]]));
        $this->assertSame([[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']], ArrayHelper::crossJoin([1, 2], ['a', 'b']));

        $divided = ArrayHelper::divide(['a' => 1, 'b' => 2]);
        $this->assertSame(['a', 'b'], $divided[0]);
        $this->assertSame([1, 2], $divided[1]);

        $this->assertSame([1, 2, 3, 4], ArrayHelper::flatten([1, [2, [3, 4]]]));
        $this->assertSame([1, 2, 3], ArrayHelper::flatten([1, [2, [3]]], 2));

        $this->assertSame([1 => 2, 2 => 3], ArrayHelper::take([1, 2, 3], -2));
        $this->assertSame([1, 2], ArrayHelper::take([1, 2, 3], 2));

        $this->assertSame([], ArrayHelper::wrap(null));
        $this->assertSame([5], ArrayHelper::wrap(5));
    }

    /**
     * Проверяет from/every/some/first/last.
     */
    #[Test]
    public function testFromEverySomeFirstLast(): void
    {
        $this->assertSame([], ArrayHelper::from(null));
        $this->assertSame(['a' => 1], ArrayHelper::from(new ArrayObject(['a' => 1])));

        $this->assertTrue(ArrayHelper::every([1, 2, 3]));
        $this->assertFalse(ArrayHelper::every([1, 0]));
        $this->assertTrue(ArrayHelper::some([0, 0, 1]));

        $this->assertSame(2, ArrayHelper::first([1, 2], fn ($v) => $v > 1));
        $this->assertSame('fallback', ArrayHelper::last([], null, 'fallback'));
        $this->assertSame(3, ArrayHelper::last([1, 2, 3]));
    }

    /**
     * Проверяет hasAll/hasAny/isAssoc/isList/join.
     */
    #[Test]
    public function testHasAllAnyIsAssocIsListJoin(): void
    {
        $data = ['a' => ['b' => 1], 'c' => 2];

        $this->assertTrue(ArrayHelper::hasAll($data, ['a.b', 'c']));
        $this->assertTrue(ArrayHelper::hasAny($data, ['missing', 'a.b']));
        $this->assertFalse(ArrayHelper::hasAny($data, ['missing']));

        $this->assertTrue(ArrayHelper::isAssoc(['a' => 1]));
        $this->assertTrue(ArrayHelper::isList([1, 2]));

        $this->assertSame('a, b and c', ArrayHelper::join(['a', 'b', 'c'], ', ', ' and '));
    }

    /**
     * Проверяет keyBy/pluck/select.
     */
    #[Test]
    public function testKeyByPluckSelect(): void
    {
        $items = [
            ['id' => 10, 'name' => 'A'],
            ['id' => 20, 'name' => 'B'],
        ];

        $this->assertSame(['10' => $items[0], '20' => $items[1]], ArrayHelper::keyBy($items, 'id'));
        $this->assertSame(['A', 'B'], ArrayHelper::pluck($items, 'name'));
        $this->assertSame(['10' => 'A', '20' => 'B'], ArrayHelper::pluck($items, 'name', 'id'));

        $selected = ArrayHelper::select($items, ['id']);
        $this->assertSame([['id' => 10], ['id' => 20]], array_values($selected));
    }

    /**
     * Проверяет map/partition/withKeys/spread.
     */
    #[Test]
    public function testMapPartitionWithKeysSpread(): void
    {
        $this->assertSame([2, 4], ArrayHelper::map([1, 2], fn ($v) => $v * 2));
        $this->assertSame([3, 7], ArrayHelper::mapSpread([[1, 2], [3, 4]], fn ($a, $b) => $a + $b));
        $this->assertSame(['a' => 1, 'b' => 2], ArrayHelper::mapWithKeys(['x' => 1, 'y' => 2], fn ($v, $k) => [$k === 'x' ? 'a' : 'b' => $v]));

        [$passed, $failed] = ArrayHelper::partition([1, 2, 3], fn ($v) => $v > 1);
        $this->assertSame([1 => 2, 2 => 3], $passed);
        $this->assertSame([0 => 1], $failed);
    }

    /**
     * Проверяет prepend/pull/push/prependKeysWith.
     */
    #[Test]
    public function testPrependPullPushPrependKeysWith(): void
    {
        $this->assertSame([0, 1, 2], ArrayHelper::prepend([1, 2], 0));
        $this->assertSame(['x' => 9, 'a' => 1], ArrayHelper::prepend(['a' => 1], 9, 'x'));

        $this->assertSame(['p_a' => 1], ArrayHelper::prependKeysWith(['a' => 1], 'p_'));

        $data = ['a' => ['list' => [1]]];
        $data = ArrayHelper::push($data, 2, 'a.list');
        $this->assertSame([1, 2], $data['a']['list']);

        $value = ArrayHelper::pull($data, 'a.list');
        $this->assertSame([1, 2], $value);
        $this->assertFalse(ArrayHelper::has($data, 'a.list'));
    }

    /**
     * Проверяет query/random/shuffle/reject/where/whereNotNull.
     */
    #[Test]
    public function testQueryRandomShuffleRejectWhere(): void
    {
        $this->assertSame('a=1&b=2', ArrayHelper::query(['a' => 1, 'b' => 2]));

        $one = ArrayHelper::random([1, 2, 3]);
        $this->assertContains($one, [1, 2, 3]);

        $two = ArrayHelper::random([1, 2, 3], 2);
        $this->assertCount(2, $two);

        $this->assertSame([], ArrayHelper::random([1, 2, 3], 0));

        $shuffled = ArrayHelper::shuffle([1, 2, 3], 42);
        $sorted   = $shuffled;
        sort($sorted);
        $this->assertSame([1, 2, 3], $sorted);

        $rejected = ArrayHelper::reject([1, 2, 3], fn ($v) => $v > 1);
        $this->assertSame([0 => 1], $rejected);

        $this->assertSame([0 => 1, 2 => 3], ArrayHelper::where([1, 2, 3], fn ($v) => $v !== 2));
        $this->assertSame([0 => 1, 1 => 2], ArrayHelper::whereNotNull([1, 2, null]));
    }

    /**
     * Проверяет sort/sortDesc/sortRecursive/sole/toCss*.
     */
    #[Test]
    public function testSortSortRecursiveSoleToCss(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], ArrayHelper::sort(['b' => 2, 'a' => 1]));
        $this->assertSame(['b' => 2, 'a' => 1], ArrayHelper::sortDesc(['a' => 1, 'b' => 2]));

        $sorted = ArrayHelper::sortRecursive(['b' => ['z' => 2, 'a' => 1], 'a' => 3]);
        $this->assertSame(['a' => 3, 'b' => ['a' => 1, 'z' => 2]], $sorted);

        $this->assertSame(1, ArrayHelper::sole([1]));

        $this->assertSame('btn active', ArrayHelper::toCssClasses(['btn', 'active' => true, 'disabled' => false]));
        $this->assertSame('color: red; margin: 0;', ArrayHelper::toCssStyles(['color' => 'red', 'margin' => '0']));
    }
}
