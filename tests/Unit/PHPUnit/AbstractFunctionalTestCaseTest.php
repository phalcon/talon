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

use Phalcon\Talon\PHPUnit\AbstractFunctionalTestCase;

final class AbstractFunctionalTestCaseTest extends AbstractFunctionalTestCase
{
    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../../Fixtures/App/app.php';
    }

    public function testDispatch(): void
    {
        $this->dispatch('/test/hello');

        $this->assertController('test');
        $this->assertResponseContentContains('Nikos');
    }
}
