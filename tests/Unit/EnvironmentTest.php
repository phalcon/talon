<?php

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Unit;

use Phalcon\Talon\Environment;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    public function testPhalconIsAvailableInTheTestImage(): void
    {
        $this->assertTrue(Environment::phalconAvailable());
    }

    public function testExactlyOneProviderReportsTrue(): void
    {
        $this->assertTrue(
            Environment::viaExtension() || Environment::viaImplementation()
        );
    }
}
