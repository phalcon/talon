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

use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;

final class AbstractUnitTestCaseTest extends AbstractUnitTestCase
{
    public function testInheritsTraitHelpers(): void
    {
        $this->assertSame('/x/', $this->getDirSeparator('/x'));

        $this->checkPhalconAvailable();
        $this->addToAssertionCount(1);
    }
}
