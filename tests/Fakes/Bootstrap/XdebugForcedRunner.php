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

namespace Phalcon\Talon\Tests\Fakes\Bootstrap;

use Phalcon\Talon\Bootstrap\Runner;

/**
 * Forces initEnvironment()'s xdebug-tuning branch to run regardless of
 * whether the xdebug extension is actually loaded - the test suite's own
 * coverage driver (pcov) doesn't reliably load it, so that branch would
 * otherwise never execute.
 */
final class XdebugForcedRunner extends Runner
{
    protected function isExtensionLoaded(string $extension): bool
    {
        return true;
    }
}
