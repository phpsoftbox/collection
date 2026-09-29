<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;

use function strlen;

#[CoversClass(Collection::class)]
#[CoversMethod(Collection::class, 'sum')]
#[CoversMethod(Collection::class, 'max')]
#[CoversMethod(Collection::class, 'min')]
#[CoversMethod(Collection::class, 'duplicates')]
#[CoversMethod(Collection::class, 'unique')]
#[CoversMethod(Collection::class, 'indexBy')]
#[CoversMethod(Collection::class, 'sortBy')]
#[CoversMethod(Collection::class, 'percentage')]
final class CollectionSelectorTest extends TestCase
{
    /**
     * Проверим, что sum('count') суммирует поле count, а не вызывает функцию PHP count().
     *
     * @see Collection::sum()
     */
    #[Test]
    public function sumTreatsFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['count' => 5],
            ['count' => 7],
        ]);

        $this->assertSame(12, $c->sum('count'));
    }

    /**
     * Проверим, что max('count') и min('count') берут значения поля count.
     *
     * @see Collection::max()
     * @see Collection::min()
     */
    #[Test]
    public function maxAndMinTreatFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['count' => 5],
            ['count' => 70],
        ]);

        $this->assertSame(70, $c->max('count'));
        $this->assertSame(5, $c->min('count'));
    }

    /**
     * Проверим, что duplicates('date') ищет дубликаты по полю date, а не вызывает date().
     *
     * @see Collection::duplicates()
     */
    #[Test]
    public function duplicatesTreatsFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['date' => '2024-01-01'],
            ['date' => '2024-01-02'],
            ['date' => '2024-01-01'],
        ]);

        $this->assertSame([2 => '2024-01-01'], $c->duplicates('date')->all());
    }

    /**
     * Проверим, что unique('trim') берёт поле trim, а строка-функция не вызывается.
     *
     * @see Collection::unique()
     */
    #[Test]
    public function uniqueTreatsFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['trim' => 'a', 'id' => 1],
            ['trim' => 'a', 'id' => 2],
            ['trim' => 'b', 'id' => 3],
        ]);

        $this->assertSame(
            [['trim' => 'a', 'id' => 1], ['trim' => 'b', 'id' => 3]],
            $c->unique('trim')->all(),
        );
    }

    /**
     * Проверим, что indexBy('key') индексирует по полю key, а не вызывает key().
     *
     * @see Collection::indexBy()
     */
    #[Test]
    public function indexByTreatsFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['key' => 'a'],
            ['key' => 'b'],
        ]);

        $this->assertSame(['a' => ['key' => 'a'], 'b' => ['key' => 'b']], $c->indexBy('key')->all());
    }

    /**
     * Проверим, что sortBy('count') сортирует по полю count.
     *
     * @see Collection::sortBy()
     */
    #[Test]
    public function sortByTreatsFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['count' => 3],
            ['count' => 1],
        ]);

        $this->assertSame([1 => ['count' => 1], 0 => ['count' => 3]], $c->sortBy('count')->all());
    }

    /**
     * Проверим, что percentage('count') считает долю элементов с истинным полем count.
     *
     * @see Collection::percentage()
     */
    #[Test]
    public function percentageTreatsFunctionNameAsFieldPath(): void
    {
        $c = new Collection([
            ['count' => 0],
            ['count' => 3],
        ]);

        $this->assertSame(50.0, $c->percentage('count'));
    }

    /**
     * Проверим, что Closure вычисляет значение и получает ключ элемента.
     *
     * @see Collection::sum()
     */
    #[Test]
    public function sumAcceptsClosure(): void
    {
        $c = new Collection(['a' => 'xx', 'b' => 'yyy']);

        $this->assertSame(5, $c->sum(static fn (string $value): int => strlen($value)));
        $this->assertSame(2, $c->sum(static fn (string $value, string $key): int => $key === 'a' ? 2 : 0));
    }

    /**
     * Проверим, что callable-массив не принимается: вычисление — только Closure.
     *
     * @see Collection::sum()
     */
    #[Test]
    public function sumRejectsCallableArray(): void
    {
        $c = new Collection([1, 2]);

        $this->expectException(TypeError::class);

        /** @phpstan-ignore argument.type */
        $c->sum([$this, 'sumRejectsCallableArray']);
    }
}
