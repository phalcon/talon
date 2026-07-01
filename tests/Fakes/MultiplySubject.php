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

namespace Phalcon\Talon\Tests\Fakes;

class MultiplySubject
{
    private int $value = 2;

    protected function multiply(int $n): int
    {
        return $this->value * $n;
    }
}
