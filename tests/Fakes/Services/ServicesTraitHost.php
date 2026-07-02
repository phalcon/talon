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

namespace Phalcon\Talon\Tests\Fakes\Services;

use Phalcon\Talon\Traits\ServicesTrait;
use PHPUnit\Framework\TestCase;

/**
 * Composes ServicesTrait without redefining any of its methods, so the
 * trait's own method declarations (and their visibility) stay in effect
 * and can be exercised from outside the composing class. Deliberately not
 * final: subclasses override the protected seams.
 */
class ServicesTraitHost extends TestCase
{
    use ServicesTrait;

    public function __construct()
    {
        parent::__construct('servicesTraitHost');
    }
}
