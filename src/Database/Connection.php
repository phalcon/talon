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

namespace Phalcon\Talon\Database;

use PDO;
use Phalcon\Talon\Contracts\Connection as ConnectionContract;
use Phalcon\Talon\Contracts\Settings;
use Phalcon\Talon\Exceptions\SchemaFileNotFound;

use function file_exists;
use function file_get_contents;
use function implode;
use function is_string;

final class Connection implements ConnectionContract
{
    private ?PDO $pdo = null;

    public function __construct(
        private Settings $settings,
        private string $driver
    ) {
    }

    public function execute(string $sql): void
    {
        $this->getPdo()->exec($sql);
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $options  = $this->settings->getDatabaseOptions($this->driver);
            $username = isset($options['username']) && is_string($options['username']) ? $options['username'] : null;
            $password = isset($options['password']) && is_string($options['password']) ? $options['password'] : null;

            $this->pdo = new PDO(
                $this->settings->getDatabaseDsn($this->driver),
                $username,
                $password
            );

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->driver === 'sqlite') {
                $this->pdo->exec('PRAGMA journal_mode = WAL');
            }

            // Before initial_queries, so those can rely on the search path.
            if ($this->driver === 'pgsql') {
                $schema = $options['schema'] ?? '';
                if (is_string($schema) && $schema !== '') {
                    $this->pdo->exec(
                        'SET search_path TO ' . Dialect::Pgsql->quoteIdentifier($schema)
                    );
                }
            }

            $initialQueries = $this->settings->get('initial_queries', '');
            if (is_string($initialQueries) && $initialQueries !== '') {
                $this->pdo->exec($initialQueries);
            }
        }

        return $this->pdo;
    }

    public function loadSchema(string $dumpFile): void
    {
        if (!file_exists($dumpFile)) {
            throw new SchemaFileNotFound($dumpFile);
        }

        $sql = (string) file_get_contents($dumpFile);

        foreach (StatementSplitter::split($sql) as $statement) {
            $this->getPdo()->exec($statement);
        }
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function select(string $table, array $criteria = []): array
    {
        $dialect = Dialect::fromPdo($this->getPdo());

        $where  = [];
        $params = [];
        $index  = 0;

        foreach ($criteria as $key => $value) {
            $column = $dialect->quoteIdentifier((string) $key);

            // `col = :p` never matches NULL in any dialect; bind no parameter.
            if ($value === null) {
                $where[] = $column . ' IS NULL';
                continue;
            }

            $placeholder          = 'p' . $index++;
            $where[]              = $column . ' = :' . $placeholder;
            $params[$placeholder] = $value;
        }

        $sql = 'SELECT * FROM ' . $dialect->quoteIdentifier($table);
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $statement = $this->getPdo()->prepare($sql);
        $statement->execute($params);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
