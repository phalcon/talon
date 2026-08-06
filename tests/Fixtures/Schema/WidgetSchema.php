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

final class WidgetSchema extends AbstractSchema
{
    protected string $table = 'widgets';

    public function insert(int $id, string $label): int
    {
        return $this->execute(
            'INSERT INTO widgets (id, label) VALUES (:id, :label)',
            [':id' => $id, ':label' => $label]
        );
    }

    protected function getStatementsMysql(): array
    {
        return ['CREATE TABLE widgets (id INT PRIMARY KEY, label VARCHAR(64));'];
    }

    protected function getStatementsPgsql(): array
    {
        return ['CREATE TABLE widgets (id INTEGER PRIMARY KEY, label VARCHAR(64));'];
    }

    protected function getStatementsSqlite(): array
    {
        return ['CREATE TABLE widgets (id INTEGER PRIMARY KEY, label TEXT);'];
    }
}
