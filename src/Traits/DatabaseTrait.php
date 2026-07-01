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

namespace Phalcon\Talon\Traits;

use Phalcon\Talon\Contracts\Connection as ConnectionContract;
use Phalcon\Talon\Contracts\Settings;
use Phalcon\Talon\Database\Connection;
use Phalcon\Talon\Talon;

use function getenv;
use function is_string;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait DatabaseTrait
{
    /** @var array<string, ConnectionContract> */
    private static array $connections = [];

    public static function resetConnections(): void
    {
        self::$connections = [];
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function assertInDatabase(string $table, array $criteria = []): void
    {
        $this->assertNotEmpty(
            $this->getFromDatabase($table, $criteria),
            "Failed asserting that a row exists in '{$table}'"
        );
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function assertNotInDatabase(string $table, array $criteria = []): void
    {
        $this->assertEmpty(
            $this->getFromDatabase($table, $criteria),
            "Failed asserting that no row exists in '{$table}'"
        );
    }

    public function getConnection(): ConnectionContract
    {
        $driver = $this->databaseDriver();

        if (!isset(self::$connections[$driver])) {
            $connection = new Connection($this->getSettings(), $driver);

            $dumpFile = $this->getSettings()->get('dump_file');
            if (is_string($dumpFile) && $dumpFile !== '') {
                $connection->loadSchema($dumpFile);
            }

            self::$connections[$driver] = $connection;
        }

        return self::$connections[$driver];
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFromDatabase(string $table, array $criteria = []): array
    {
        return $this->getConnection()->select($table, $criteria);
    }

    public function getSettings(): Settings
    {
        return Talon::settings();
    }

    private function databaseDriver(): string
    {
        $driver = getenv('driver');

        return $driver !== false ? $driver : 'sqlite';
    }
}
