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

use Phalcon\Talon\Tests\Fakes\MultiplySubject;
use Phalcon\Talon\Tests\Fakes\ReflectionSubject;
use Phalcon\Talon\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ReflectionTraitTest extends TestCase
{
    use ReflectionTrait;

    public function testCallProtectedMethodAndProperties(): void
    {
        $subject = new MultiplySubject();

        $this->assertSame(6, $this->callProtectedMethod($subject, 'multiply', 3));
        $this->assertSame(2, $this->getProtectedProperty($subject, 'value'));

        $this->setProtectedProperty($subject, 'value', 9);
        $this->assertSame(9, $this->getProtectedProperty($subject, 'value'));
    }

    public function testInvokeMethod(): void
    {
        $subject = new ReflectionSubject(10);

        $this->assertSame(15, $this->invokeMethod($subject, 'plusBase', [5]));
    }

    public function testPublicApiVisibility(): void
    {
        // Execute each helper so this test covers the mutated method bodies
        // (infection pairs tests with mutants via line coverage).
        $subject = new MultiplySubject();
        $this->callProtectedMethod($subject, 'multiply', 2);
        $this->getProtectedProperty($subject, 'value');
        $this->setProtectedProperty($subject, 'value', 3);
        $this->invokeMethod(new ReflectionSubject(1), 'plusBase', [1]);

        foreach (
            [
                'callProtectedMethod',
                'getProtectedProperty',
                'invokeMethod',
                'setProtectedProperty',
            ] as $method
        ) {
            $this->assertTrue(
                (new ReflectionMethod(self::class, $method))->isPublic(),
                $method
            );
        }
    }

    public function testWorksWithClassStrings(): void
    {
        // No instance: callProtectedMethod builds one without the constructor.
        $this->assertSame(8, $this->callProtectedMethod(ReflectionSubject::class, 'double', 4));

        // Static property access via class-string.
        $this->assertSame(5, $this->getProtectedProperty(ReflectionSubject::class, 'counter'));

        $this->setProtectedProperty(ReflectionSubject::class, 'counter', 9);
        $this->assertSame(9, $this->getProtectedProperty(ReflectionSubject::class, 'counter'));
    }
}
