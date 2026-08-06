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

class SchemaConnectionMissing extends Exception
{
    public function __construct(string $table)
    {
        parent::__construct(
            "Schema fixture for '" . $table . "' has no database connection"
        );
    }
}
