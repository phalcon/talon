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

namespace Phalcon\Talon\Tests\Fixtures\Schema;

use Phalcon\Talon\Database\Schema\AbstractSchema;

/**
 * Present in every dialect and dependent on `widgets`, which sorts before it -
 * the shape `addTable()`'s dependency enforcement needs.
 */
final class ZoneSchema extends AbstractSchema
{
    protected string $table = 'zones';

    public function getDependencies(): array
    {
        return ['widgets'];
    }

    protected function getStatementsMysql(): array
    {
        return ['CREATE TABLE zones (id INT PRIMARY KEY, widget_id INT);'];
    }

    protected function getStatementsPgsql(): array
    {
        return ['CREATE TABLE zones (id INTEGER PRIMARY KEY, widget_id INTEGER);'];
    }

    protected function getStatementsSqlite(): array
    {
        return ['CREATE TABLE zones (id INTEGER PRIMARY KEY, widget_id INTEGER);'];
    }
}
