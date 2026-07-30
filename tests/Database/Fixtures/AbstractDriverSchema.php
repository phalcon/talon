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

use Phalcon\Talon\Database\Dialect;

/**
 * Per-dialect schema statements. The methods are abstract on purpose: a new
 * dialect cannot be silently forgotten by a fixture that only implements some
 * of them.
 */
abstract class AbstractDriverSchema
{
    /**
     * @return list<string>
     */
    public function sqlFor(Dialect $dialect): array
    {
        return match ($dialect) {
            Dialect::Mysql  => $this->sqlMysql(),
            Dialect::Pgsql  => $this->sqlPgsql(),
            Dialect::Sqlite => $this->sqlSqlite(),
        };
    }

    /**
     * @return list<string>
     */
    abstract protected function sqlMysql(): array;

    /**
     * @return list<string>
     */
    abstract protected function sqlPgsql(): array;

    /**
     * @return list<string>
     */
    abstract protected function sqlSqlite(): array;
}
