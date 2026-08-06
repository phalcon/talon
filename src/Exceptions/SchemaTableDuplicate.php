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

class SchemaTableDuplicate extends Exception
{
    public function __construct(string $table, string $dialect)
    {
        parent::__construct(
            "Two schema definitions both declare the table '" . $table
            . "' for the '" . $dialect . "' dialect. Table names are the artifact key, "
            . 'so the second would overwrite the first.'
        );
    }
}
