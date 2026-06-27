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
use PHPUnit\Framework\SkippedTestSuiteError;

final class AbstractUnitTestCaseTest extends AbstractUnitTestCase
{
    public function testInheritsTraitHelpers(): void
    {
        $this->assertSame('/x/', $this->getDirSeparator('/x'));

        $this->checkPhalconAvailable();
        $this->addToAssertionCount(1);
    }

    public function testCheckExtensionIsLoadedPassesForLoadedExtension(): void
    {
        $this->checkExtensionIsLoaded('json');

        $this->addToAssertionCount(1);
    }

    public function testCheckExtensionIsLoadedThrowsForMissingExtension(): void
    {
        try {
            $this->checkExtensionIsLoaded('a_missing_extension');
            $this->fail('Expected a SkippedTestSuiteError');
        } catch (SkippedTestSuiteError $exception) {
            $this->assertStringContainsString('not loaded', $exception->getMessage());
        }
    }
}
