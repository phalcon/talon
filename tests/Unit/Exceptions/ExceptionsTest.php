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
use Phalcon\Talon\Exceptions\InvalidConfiguration;
use Phalcon\Talon\Exceptions\InvalidResultsetClass;
use Phalcon\Talon\Exceptions\MissingService;
use Phalcon\Talon\Exceptions\PhalconNotAvailable;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Exceptions\SchemaFileNotFound;
use Phalcon\Talon\Exceptions\UnknownDriver;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testBaseImplementsContract(): void
    {
        $this->assertInstanceOf(TalonThrowable::class, new Exception('x'));
    }

    public function testGranularExceptionMessagesAreExact(): void
    {
        $this->assertSame(
            "appFactory() must return an object with handle(); got 'stdClass'",
            (new InvalidApplication('stdClass'))->getMessage()
        );
        $this->assertSame(
            'Invalid configuration: something is wrong',
            (new InvalidConfiguration('something is wrong'))->getMessage()
        );
        $this->assertSame(
            "'stdClass' is not a Phalcon\\Mvc\\Model\\Resultset subclass",
            (new InvalidResultsetClass('stdClass'))->getMessage()
        );
        $this->assertSame(
            "The application DI has no 'dispatcher' service",
            (new MissingService('dispatcher'))->getMessage()
        );
        $this->assertSame(
            "Schema file not found: 'storage/schema.sql'",
            (new SchemaFileNotFound('storage/schema.sql'))->getMessage()
        );
        $this->assertSame(
            "Unknown database driver 'oracle'",
            (new UnknownDriver('oracle'))->getMessage()
        );
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
