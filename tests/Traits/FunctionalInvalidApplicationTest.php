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

use Phalcon\Talon\Exceptions\InvalidApplication;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;
use stdClass;

final class FunctionalInvalidApplicationTest extends TestCase
{
    use FunctionalTrait;

    public function testDispatchRejectsAppWithoutHandle(): void
    {
        $this->expectException(InvalidApplication::class);

        $this->dispatch('/');
    }

    protected function appFactory(): callable
    {
        return static fn () => new stdClass();
    }
}
