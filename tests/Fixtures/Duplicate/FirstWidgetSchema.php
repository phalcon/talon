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

namespace Phalcon\Talon\Tests\Fixtures\Duplicate;

use Phalcon\Talon\Database\Schema\AbstractSchema;

/**
 * Declares the same table as SecondWidgetSchema on purpose - the writer must
 * refuse rather than silently overwrite.
 */
final class FirstWidgetSchema extends AbstractSchema
{
    protected string $table = 'widgets';

    protected function getStatementsMysql(): array
    {
        return ['CREATE TABLE widgets (id INT PRIMARY KEY);'];
    }

    protected function getStatementsPgsql(): array
    {
        return ['CREATE TABLE widgets (id INTEGER PRIMARY KEY);'];
    }

    protected function getStatementsSqlite(): array
    {
        return ['CREATE TABLE widgets (id INTEGER PRIMARY KEY);'];
    }
}
