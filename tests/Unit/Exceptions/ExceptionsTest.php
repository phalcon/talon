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

namespace Phalcon\Talon\Tests\Unit\Exceptions;

use Phalcon\Talon\Contracts\Throwable as TalonThrowable;
use Phalcon\Talon\Exceptions\Exception;
use Phalcon\Talon\Exceptions\InvalidApplication;
use Phalcon\Talon\Exceptions\MissingService;
use Phalcon\Talon\Exceptions\PhalconNotAvailable;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Exceptions\UnknownDriver;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testBaseImplementsContract(): void
    {
        $this->assertInstanceOf(TalonThrowable::class, new Exception('x'));
    }

    public function testGranularExtendsBaseAndCarriesMessage(): void
    {
        $e = new UnknownDriver('oracle');

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertInstanceOf(TalonThrowable::class, $e);
        $this->assertStringContainsString('oracle', $e->getMessage());
    }

    public function testRemainingGranularExceptionsCarryMessages(): void
    {
        $this->assertStringContainsString('handle', (new InvalidApplication('stdClass'))->getMessage());
        $this->assertStringContainsString('dispatcher', (new MissingService('dispatcher'))->getMessage());
        $this->assertStringContainsString('dispatch', (new ResponseNotDispatched())->getMessage());
        $this->assertStringContainsString('Phalcon', (new PhalconNotAvailable())->getMessage());
    }
}
