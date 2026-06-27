<?php

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
        $where  = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            $where[]      = $key . ' = :' . $key;
            $params[$key] = $value;
        }

        $sql = 'SELECT * FROM ' . $table;
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
