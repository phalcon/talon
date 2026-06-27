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

class MissingService extends Exception
{
    public function __construct(string $service)
    {
        parent::__construct("The application DI has no '" . $service . "' service");
    }
}
