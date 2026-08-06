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

/**
 * Run-time half of a schema fixture: the lifecycle a test drives. Data shape
 * is deliberately absent - `insert()` stays each concrete class's own API.
 */
interface SchemaFixture
{
    /**
     * @return int rows removed
     */
    public function clear(): int;

    public function create(): void;

    public function drop(): void;
}
