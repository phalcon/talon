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

use Phalcon\Talon\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;

final class ReflectionTraitTest extends TestCase
{
    use ReflectionTrait;

    public function testCallProtectedMethodAndProperties(): void
    {
        $subject = new class () {
            private int $value = 2;

            protected function multiply(int $n): int
            {
                return $this->value * $n;
            }
        };

        $this->assertSame(6, $this->callProtectedMethod($subject, 'multiply', 3));
        $this->assertSame(2, $this->getProtectedProperty($subject, 'value'));

        $this->setProtectedProperty($subject, 'value', 9);
        $this->assertSame(9, $this->getProtectedProperty($subject, 'value'));
    }
}
