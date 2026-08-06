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

namespace Phalcon\Talon\Database\Schema;

use Phalcon\Talon\Database\Dialect;

/**
 * Build-time half of a schema fixture: everything the generator needs in order
 * to emit a dump, with no database connection involved.
 */
interface SchemaDefinition
{
    /**
     * Table names that must exist before this one.
     *
     * @return list<string>
     */
    public function getDependencies(): array;

    /**
     * Creation statements only - the generator prepends the DROP itself. An
     * empty list means this table does not exist in the given dialect.
     *
     * @return list<string>
     */
    public function getStatements(Dialect $dialect): array;

    public function getTable(): string;
}
