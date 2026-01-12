<?php

declare(strict_types=1);

namespace PhpSoftBox\Collection\Tests;

use PhpSoftBox\Collection\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionMergeTest extends TestCase
{
    public function testRecursiveReplaceByDefault(): void
    {
        $left = new Collection([
            'db'    => ['host' => 'localhost', 'ports' => [3306, 3307]],
            'debug' => false,
        ]);

        $right = [
                    'db'    => ['host' => 'remote', 'ports' => [4406]],
                    'debug' => true,
                ];
        $merged = $left->merge($right); // recursive:true, list: replace
        $this->assertSame('remote', $merged->getPath('db.host'));
        // списки по умолчанию заменяются
        $this->assertSame([4406], $merged->getPath('db.ports'));
        $this->assertTrue($merged->getPath('debug'));
    }

    public function testListAppend(): void
    {
        $left = new Collection(['list' => [1, 2]]);

        $right  = ['list' => [3, 4]];
        $merged = $left->merge($right, ['recursive' => true, 'list' => 'append']);
        $this->assertSame([1,2,3,4], $merged->getPath('list'));
    }

    public function testListAppendUnique(): void
    {
        $left = new Collection(['list' => [1, 2, 2]]);

        $right  = ['list' => [2, 3, 3]];
        $merged = $left->merge($right, ['recursive' => true, 'list' => 'append_unique']);
        $this->assertSame([1,2,2,3], $merged->getPath('list'));
    }

    public function testNonRecursiveReplace(): void
    {
        $left = new Collection(['db' => ['host' => 'a'], 'mode' => 'dev']);

        $right  = ['db' => ['host' => 'b']];
        $merged = $left->merge($right, false); // не рекурсивно
        // заменился весь подмассив, без углубления
        $this->assertSame(['host' => 'b'], $merged->getPath('db'));
        $this->assertSame('dev', $merged->getPath('mode'));
    }
}
