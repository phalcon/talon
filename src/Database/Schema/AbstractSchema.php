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

use PDO;
use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Exceptions\SchemaConnectionMissing;
use PHPUnit\Framework\Assert;

use function sprintf;

/**
 * Base for a schema fixture. Build-time methods work with no connection so the
 * generator can instantiate freely; run-time methods require one.
 */
abstract class AbstractSchema implements SchemaDefinition, SchemaFixture
{
    protected string $table = '';

    final public function __construct(
        protected ?PDO $connection = null,
        bool $withClear = true
    ) {
        if ($withClear) {
            $this->clear();
        }
    }

    public function clear(): int
    {
        if ($this->connection === null) {
            return 0;
        }

        $table = $this->quotedTable();

        return match ($this->dialect()) {
            Dialect::Mysql  => $this->clearMysql($table),
            Dialect::Pgsql  => (int) $this->requireConnection()->exec(
                'TRUNCATE TABLE ' . $table . ' RESTART IDENTITY CASCADE;'
            ),
            Dialect::Sqlite => (int) $this->requireConnection()->exec(
                'DELETE FROM ' . $table . ';'
            ),
        };
    }

    public function create(): void
    {
        $connection = $this->requireConnection();

        foreach ($this->getStatements($this->dialect()) as $statement) {
            $connection->exec($statement);
        }
    }

    public function drop(): void
    {
        $this->requireConnection()->exec(
            'DROP TABLE IF EXISTS ' . $this->quotedTable() . ';'
        );
    }

    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function getStatements(Dialect $dialect): array
    {
        return match ($dialect) {
            Dialect::Mysql  => $this->getStatementsMysql(),
            Dialect::Pgsql  => $this->getStatementsPgsql(),
            Dialect::Sqlite => $this->getStatementsSqlite(),
        };
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setConnection(PDO $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Postgres serial columns do not advance when an explicit id is inserted.
     */
    protected function advanceSequence(string $column, int $id): void
    {
        if ($this->connection === null || $this->dialect() !== Dialect::Pgsql) {
            return;
        }

        $this->requireConnection()->exec(sprintf(
            "SELECT setval(pg_get_serial_sequence('%s', '%s'), %d)",
            $this->table,
            $column,
            $id
        ));
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->requireConnection()->prepare($sql);
        $statement->execute($params);

        $result = $statement->rowCount();
        if ($result === 0) {
            Assert::fail(sprintf(
                "Failed to insert row into table '%s' using '%s' driver",
                $this->table,
                $this->dialect()->value
            ));
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    abstract protected function getStatementsMysql(): array;

    /**
     * @return list<string>
     */
    abstract protected function getStatementsPgsql(): array;

    /**
     * @return list<string>
     */
    abstract protected function getStatementsSqlite(): array;

    private function clearMysql(string $table): int
    {
        $connection = $this->requireConnection();

        $connection->exec('SET FOREIGN_KEY_CHECKS=0;');
        $result = (int) $connection->exec('TRUNCATE TABLE ' . $table . ';');
        $connection->exec('SET FOREIGN_KEY_CHECKS=1;');

        return $result;
    }

    private function dialect(): Dialect
    {
        return Dialect::fromPdo($this->requireConnection());
    }

    private function quotedTable(): string
    {
        return $this->dialect()->quoteIdentifier($this->table);
    }

    private function requireConnection(): PDO
    {
        return $this->connection ?? throw new SchemaConnectionMissing($this->table);
    }
}
