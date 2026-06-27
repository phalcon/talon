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

namespace Phalcon\Talon\Tests\Fixtures;

class ReflectionSubject
{
    protected static int $counter = 5;

    private int $base;

    public function __construct(int $base)
    {
        $this->base = $base;
    }

    protected function double(int $number): int
    {
        return $number * 2;
    }

    protected function plusBase(int $number): int
    {
        return $this->base + $number;
    }
}
