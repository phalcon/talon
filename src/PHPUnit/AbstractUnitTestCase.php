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

namespace Phalcon\Talon\PHPUnit;

use Phalcon\Talon\Environment;
use Phalcon\Talon\Traits\FileSystemTrait;
use Phalcon\Talon\Traits\ReflectionTrait;
use PHPUnit\Framework\SkippedTestSuiteError;
use PHPUnit\Framework\TestCase;

use function extension_loaded;
use function sprintf;

abstract class AbstractUnitTestCase extends TestCase
{
    use ReflectionTrait;
    use FileSystemTrait;

    public function checkExtensionIsLoaded(string $extension): void
    {
        if (!extension_loaded($extension)) {
            throw new SkippedTestSuiteError(
                sprintf("Extension '%s' is not loaded. Skipping test", $extension)
            );
        }
    }

    public function checkPhalconAvailable(): void
    {
        // @codeCoverageIgnoreStart
        // Unreachable here: the suite itself requires Phalcon, so it is always available.
        if (!Environment::phalconAvailable()) {
            throw new SkippedTestSuiteError(
                'Phalcon is not available (ext-phalcon or phalcon/phalcon). Skipping test'
            );
        }
        // @codeCoverageIgnoreEnd
    }
}
