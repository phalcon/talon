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
use ReflectionMethod;

final class ResultSetTraitTest extends TestCase
{
    use ResultSetTrait;

    public function testAcceptsResultsetSubclass(): void
    {
        $this->assertInstanceOf(Simple::class, $this->mockResultSet([], Simple::class));
    }

    public function testEmptyMock(): void
    {
        $mock = $this->mockResultSet([]);

        $this->assertCount(0, $mock);
        $this->assertNull($mock->getFirst());
    }

    public function testInvalidClassThrows(): void
    {
        $this->expectException(InvalidResultsetClass::class);
        $this->mockResultSet([], self::class);
    }

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

    public function testMockResultSetIsPublic(): void
    {
        // The call covers the method body so infection pairs this test
        // with the visibility mutant; the reflection check observes it.
        $this->mockResultSet([]);

        $this->assertTrue(
            (new ReflectionMethod(self::class, 'mockResultSet'))->isPublic()
        );
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

    public function testSeekReadsTheInjectedRows(): void
    {
        $first  = $this->createMock(ModelInterface::class);
        $second = $this->createMock(ModelInterface::class);
        $mock   = $this->mockResultSet([$first, $second]);

        // seek() is final and reads the injected rows property directly;
        // without the injected rows it cannot position onto row 1.
        $mock->seek(1);

        $this->addToAssertionCount(1);
    }
}
