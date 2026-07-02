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

namespace Phalcon\Talon\Tests\Unit\PHPUnit;

use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Talon\PHPUnit\AbstractBrowserTestCase;

final class AbstractBrowserTestCaseTest extends AbstractBrowserTestCase
{
    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../../Fakes/Browser/app.php';
    }

    public function testSetUpResetsTheDefaultDiAndClearsSession(): void
    {
        Di::setDefault(new FactoryDefault());
        $_SESSION['leftover'] = 'value';

        $this->setUp();

        $this->assertNull(Di::getDefault());
        $this->assertSame([], $_SESSION);
    }
}
