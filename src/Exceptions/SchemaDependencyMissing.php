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

class SchemaDependencyMissing extends Exception
{
    public function __construct(string $table, string $dependency)
    {
        parent::__construct(
            "Table '" . $table . "' requires '" . $dependency . "', which does not exist. "
            . "Create it first with addTable('" . $dependency . "')."
        );
    }
}
