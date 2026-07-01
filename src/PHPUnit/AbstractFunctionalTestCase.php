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

use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;

abstract class AbstractFunctionalTestCase extends AbstractUnitTestCase
{
    use FunctionalTrait;
    use FunctionalAssertionsTrait;
}
