<?php

/**
 * This file is part of the Phalcon Talon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Traits;

use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Talon\Exceptions\InvalidResultsetClass;
use Phalcon\Talon\Traits\ResultSetTrait;
use PHPUnit\Framework\TestCase;

final class ResultSetTraitTest extends TestCase
{
    use ResultSetTrait;

    public function testMockReportsCountAndFirstLast(): void
    {
        $first  = $this->createMock(ModelInterface::class);
        $middle = $this->createMock(ModelInterface::class);
        $last   = $this->createMock(ModelInterface::class);

        $mock = $this->mockResultSet([$first, $middle, $last]);

        $this->assertCount(3, $mock);
        $this->assertSame($first, $mock->getFirst());
        $this->assertSame($last, $mock->getLast());
        $this->assertSame([$first, $middle, $last], $mock->toArray());
    }

    public function testEmptyMock(): void
    {
        $mock = $this->mockResultSet([]);

        $this->assertCount(0, $mock);
        $this->assertNull($mock->getFirst());
    }

    public function testMockSupportsIteration(): void
    {
        $first  = $this->createMock(ModelInterface::class);
        $second = $this->createMock(ModelInterface::class);
        $mock   = $this->mockResultSet([$first, $second]);

        $this->assertTrue($mock->valid());
        $this->assertSame(0, $mock->key());

        $mock->next();
        $this->assertTrue($mock->valid());

        $mock->next();
        $this->assertFalse($mock->valid());
    }

    public function testInvalidClassThrows(): void
    {
        $this->expectException(InvalidResultsetClass::class);
        $this->mockResultSet([], self::class);
    }

    public function testAcceptsResultsetSubclass(): void
    {
        $this->assertInstanceOf(Simple::class, $this->mockResultSet([], Simple::class));
    }
}
