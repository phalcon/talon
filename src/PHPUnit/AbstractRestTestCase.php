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

use Phalcon\Talon\Traits\RestAssertionsTrait;
use Phalcon\Talon\Traits\RestTrait;

/**
 * Requests cross a real HTTP boundary, so - unlike AbstractBrowserTestCase -
 * there is no in-process $_SESSION to isolate between tests.
 */
abstract class AbstractRestTestCase extends AbstractUnitTestCase
{
    use RestAssertionsTrait;
    use RestTrait;
}
