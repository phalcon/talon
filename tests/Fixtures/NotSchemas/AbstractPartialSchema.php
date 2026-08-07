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

namespace Phalcon\Talon\Tests\Fixtures\NotSchemas;

use Phalcon\Talon\Database\Schema\AbstractSchema;

/**
 * A SchemaDefinition, but abstract - the collector must skip it too, so a
 * project can share a base class inside its schema directory.
 */
abstract class AbstractPartialSchema extends AbstractSchema
{
    protected string $table = 'partial';

    protected function getStatementsMysql(): array
    {
        return [];
    }

    protected function getStatementsPgsql(): array
    {
        return [];
    }
}
