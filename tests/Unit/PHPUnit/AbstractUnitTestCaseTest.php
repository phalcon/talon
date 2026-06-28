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

namespace Phalcon\Talon\Tests\Unit\PHPUnit;

use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;
use Phalcon\Talon\Tests\Fakes\MockSubject;
use PHPUnit\Framework\SkippedTestSuiteError;

final class AbstractUnitTestCaseTest extends AbstractUnitTestCase
{
    public function testInheritsTraitHelpers(): void
    {
        $this->assertSame('/x/', $this->getDirSeparator('/x'));

        $this->checkPhalconAvailable();
        $this->addToAssertionCount(1);
    }

    public function testCheckExtensionIsLoadedPassesForLoadedExtension(): void
    {
        $this->checkExtensionIsLoaded('json');

        $this->addToAssertionCount(1);
    }

    public function testCheckExtensionIsLoadedThrowsForMissingExtension(): void
    {
        try {
            $this->checkExtensionIsLoaded('a_missing_extension');
            $this->fail('Expected a SkippedTestSuiteError');
        } catch (SkippedTestSuiteError $exception) {
            $this->assertStringContainsString('not loaded', $exception->getMessage());
        }
    }

    public function testMockWithoutConstructorSkipsConstructor(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class);

        $this->assertInstanceOf(MockSubject::class, $subject);
        $this->assertSame('default', $subject->tag);
        $this->assertFalse($subject->booted);
    }

    public function testMockWithConstructorRunsConstructor(): void
    {
        $subject = $this->mockWithConstructor(MockSubject::class, ['custom']);

        $this->assertInstanceOf(MockSubject::class, $subject);
        $this->assertSame('custom', $subject->tag);
        $this->assertTrue($subject->booted);
    }

    public function testMockWithConstructorStubsMethodsDuringConstruction(): void
    {
        $subject = $this->mockWithConstructor(MockSubject::class, ['custom'], ['boot' => null]);

        $this->assertSame('custom', $subject->tag);
        $this->assertFalse($subject->booted);
    }

    public function testMockMethodOverrideReturnsValue(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, ['value' => 99]);

        $this->assertSame(99, $subject->value());
    }

    public function testMockMethodOverrideAcceptsClosure(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, [
            'greeting' => static fn (): string => 'stubbed',
        ]);

        $this->assertSame('stubbed', $subject->greeting());
    }

    public function testMockPropertyOverride(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, ['tag' => 'overridden']);

        $this->assertSame('overridden', $subject->tag);
    }
}
