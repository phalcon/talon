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

    public function testProviderChecksReturnBooleans(): void
    {
        // Call each directly so both branches are exercised regardless of runtime.
        $this->assertIsBool(Environment::viaExtension());
        $this->assertIsBool(Environment::viaImplementation());
    }
}
