<?php

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Unit\Exceptions;

use Phalcon\Talon\Contracts\Throwable as TalonThrowable;
use Phalcon\Talon\Exceptions\Exception;
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
}
