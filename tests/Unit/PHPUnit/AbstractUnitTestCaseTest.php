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

use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;
use Phalcon\Talon\Tests\Fakes\MockSubject;
use PHPUnit\Framework\SkippedTestSuiteError;
use ReflectionMethod;

final class AbstractUnitTestCaseTest extends AbstractUnitTestCase
{
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

    public function testHelperMethodVisibility(): void
    {
        // Execute each helper so this test covers the mutated method bodies
        // (infection only pairs tests with mutants via line coverage).
        $this->checkExtensionIsLoaded('json');
        $this->checkPhalconAvailable();
        $this->mockWithConstructor(MockSubject::class, ['custom']);
        $this->mockWithoutConstructor(MockSubject::class);

        $this->assertTrue((new ReflectionMethod(AbstractUnitTestCase::class, 'checkExtensionIsLoaded'))->isPublic());
        $this->assertTrue((new ReflectionMethod(AbstractUnitTestCase::class, 'checkPhalconAvailable'))->isPublic());
        $this->assertTrue((new ReflectionMethod(AbstractUnitTestCase::class, 'mockWithConstructor'))->isPublic());
        $this->assertTrue((new ReflectionMethod(AbstractUnitTestCase::class, 'mockWithoutConstructor'))->isPublic());
        $this->assertTrue((new ReflectionMethod(AbstractUnitTestCase::class, 'phalconAvailable'))->isProtected());
    }
    public function testInheritsTraitHelpers(): void
    {
        $this->assertSame('/x/', $this->getDirSeparator('/x'));

        $this->checkPhalconAvailable();
        $this->addToAssertionCount(1);
    }

    public function testMockMethodOverrideAcceptsClosure(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, [
            'greeting' => static fn (): string => 'stubbed',
        ]);

        $this->assertSame('stubbed', $subject->greeting());
    }

    public function testMockMethodOverrideReturnsValue(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, ['value' => 99]);

        $this->assertSame(99, $subject->value());
    }

    public function testMockPropertyOverride(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, ['tag' => 'overridden']);

        $this->assertSame('overridden', $subject->tag);
    }

    public function testMockStubsMultipleMethodOverrides(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class, [
            'greeting' => static fn (): string => 'stubbed',
            'value'    => 7,
        ]);

        $this->assertSame('stubbed', $subject->greeting());
        $this->assertSame(7, $subject->value());
    }

    public function testMockWithConstructorNormalizesCtorArgKeys(): void
    {
        $plain = $this->mockWithConstructor(MockSubject::class, ['label' => 'custom']);
        $this->assertSame('custom', $plain->tag);

        $stubbed = $this->mockWithConstructor(MockSubject::class, ['label' => 'custom'], ['boot' => null]);
        $this->assertSame('custom', $stubbed->tag);
        $this->assertFalse($stubbed->booted);
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

    public function testMockWithoutConstructorSkipsConstructor(): void
    {
        $subject = $this->mockWithoutConstructor(MockSubject::class);

        $this->assertInstanceOf(MockSubject::class, $subject);
        $this->assertSame('default', $subject->tag);
        $this->assertFalse($subject->booted);
    }

    public function testSetUpResetsTheDefaultDi(): void
    {
        Di::setDefault(new FactoryDefault());
        $this->assertNotNull(Di::getDefault());

        $this->setUp();

        $this->assertNull(Di::getDefault());
    }

    public function testSetUpSkipsDiResetWhenPhalconIsNotAvailable(): void
    {
        $default = new FactoryDefault();
        Di::setDefault($default);

        // A test case that reports Phalcon as unavailable must not touch the DI,
        // so packages without Phalcon/DI can still use the Talon abstracts.
        $withoutPhalcon = new class ('runSetUp') extends AbstractUnitTestCase {
            public function runSetUp(): void
            {
                $this->setUp();
            }

            protected function phalconAvailable(): bool
            {
                return false;
            }
        };

        $withoutPhalcon->runSetUp();

        $this->assertSame($default, Di::getDefault());

        Di::reset();
    }
}
