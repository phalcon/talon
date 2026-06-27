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

namespace Phalcon\Talon\Exceptions;

class PhalconNotAvailable extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'Phalcon is not available: install the ext-phalcon extension or the phalcon/phalcon package'
        );
    }
}
