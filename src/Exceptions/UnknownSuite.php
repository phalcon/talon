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

use function implode;

final class UnknownSuite extends Exception
{
    /**
     * @param list<string> $available
     */
    public function __construct(string $suite, array $available)
    {
        parent::__construct(
            "Unknown suite '" . $suite . "'. Available suites: " . implode(', ', $available)
        );
    }
}
