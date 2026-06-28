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

class MockSubject
{
    public bool $booted = false;

    public string $tag = 'default';

    public function __construct(string $tag = 'unset')
    {
        $this->tag = $tag;
        $this->boot();
    }

    public function boot(): void
    {
        $this->booted = true;
    }

    public function greeting(): string
    {
        return 'hello ' . $this->tag;
    }

    public function value(): int
    {
        return 42;
    }
}
