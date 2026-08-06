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

namespace Phalcon\Talon\Tests\Database\Fixtures;

use Phalcon\Talon\Database\Schema\AbstractSchema;

/**
 * The one table Talon's own database suites need. `talon schema` renders it
 * into resources/schema/*.sql, which every phpunit.<driver>.xml then points
 * `dump_file` at.
 */
final class UsersSchema extends AbstractSchema
{
    protected string $table = 'users';

    public function insert(int $id, string $email): int
    {
        return $this->execute(
            'INSERT INTO users (id, email) VALUES (:id, :email)',
            [':id' => $id, ':email' => $email]
        );
    }

    protected function getStatementsMysql(): array
    {
        return ['CREATE TABLE users (id INT PRIMARY KEY, email VARCHAR(255));'];
    }

    protected function getStatementsPgsql(): array
    {
        return ['CREATE TABLE users (id INT PRIMARY KEY, email VARCHAR(255));'];
    }

    protected function getStatementsSqlite(): array
    {
        return ['CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT);'];
    }
}
