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
    public function testExactlyOneProviderReportsTrue(): void
    {
        $this->assertTrue(
            Environment::viaExtension() || Environment::viaImplementation()
        );
    }
    public function testPhalconIsAvailableInTheTestImage(): void
    {
        $this->assertTrue(Environment::phalconAvailable());
    }

    public function testProvidersAreMutuallyExclusive(): void
    {
        // Exactly one provider must report true in the test image,
        // regardless of whether phalcon is the extension or the PHP package.
        $this->assertNotSame(
            Environment::viaExtension(),
            Environment::viaImplementation()
        );
    }

    public function testProvidersMatchAvailability(): void
    {
        // Evaluate each into a variable so neither is short-circuited away.
        $viaImplementation = Environment::viaImplementation();
        $viaExtension      = Environment::viaExtension();

        $this->assertSame(
            $viaExtension || $viaImplementation,
            Environment::phalconAvailable()
        );
    }
}
