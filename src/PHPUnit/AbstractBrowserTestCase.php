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

use Phalcon\Talon\Traits\BrowserAssertionsTrait;
use Phalcon\Talon\Traits\BrowserTrait;

abstract class AbstractBrowserTestCase extends AbstractUnitTestCase
{
    use BrowserTrait;
    use BrowserAssertionsTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolate session state between tests (the in-process app keeps one
        // session across requests within a test; clear it between tests).
        $_SESSION = [];
    }
}
