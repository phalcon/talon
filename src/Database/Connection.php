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
use Phalcon\Talon\Database\Schema\SchemaManifest;
use Phalcon\Talon\Exceptions\SchemaDependencyMissing;
use Phalcon\Talon\Exceptions\SchemaFileNotFound;
use Phalcon\Talon\Exceptions\SchemaManifestNotLoaded;

use function count;
use function end;
use function explode;
use function file_exists;
use function file_get_contents;
use function implode;
use function is_dir;
use function is_string;

final class Connection implements ConnectionContract
{
    private ?SchemaManifest $manifest = null;

    private ?PDO $pdo = null;

    public function __construct(
        private Settings $settings,
        private string $driver
    ) {
    }

    public function addTable(string $table): void
    {
        $manifest = $this->manifest ?? throw new SchemaManifestNotLoaded($table);

        // Dependencies are verified, never resolved. The bulk load runs with
        // _preSchema live; a standalone add does not, so an unsatisfied FK
        // fails in a dialect-specific way that points nowhere near the caller.
        foreach ($manifest->getDependencies($table) as $dependency) {
            if (!$this->tableExists($dependency)) {
                throw new SchemaDependencyMissing($table, $dependency);
            }
        }

        $this->executeFile($manifest->getFile($table));
    }

    public function execute(string $sql): void
    {
        $this->getPdo()->exec($sql);
    }

    public function getPdo(): PDO
    {
        return $this->pdo ??= $this->connect();
    }

    public function loadSchema(string $dumpFile): void
    {
        if (is_dir($dumpFile)) {
            $this->loadManifest($dumpFile);

            return;
        }

        $this->executeFile($dumpFile);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, array<string, mixed>>
     */
    public function select(string $table, array $criteria = []): array
    {
        $dialect          = Dialect::fromPdo($this->getPdo());
        [$where, $params] = $this->conditions($dialect, $criteria);

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

    public function tableExists(string $table): bool
    {
        $dialect          = Dialect::fromPdo($this->getPdo());
        [$sql, $params]   = $this->existenceQuery($dialect, $table);

        $statement = $this->getPdo()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function applyDriverDefaults(PDO $pdo, array $options): void
    {
        if ($this->driver === 'sqlite') {
            $pdo->exec('PRAGMA journal_mode = WAL');
        }

        // Before the initial queries, so those can rely on the search path.
        if ($this->driver === 'pgsql') {
            $this->applySearchPath($pdo, $options);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function applySearchPath(PDO $pdo, array $options): void
    {
        // A positive guard rather than an early return: the test environment
        // always configures a schema, so a `return` here would be a statement
        // no suite can reach.
        $schema = $options['schema'] ?? '';
        if (is_string($schema) && $schema !== '') {
            $pdo->exec('SET search_path TO ' . Dialect::Pgsql->quoteIdentifier($schema));
        }
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array{0: list<string>, 1: array<string, mixed>}
     */
    private function conditions(Dialect $dialect, array $criteria): array
    {
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

        return [$where, $params];
    }

    /**
     * Builds the connection. Everything driver-specific is delegated, so the
     * order here is the whole contract: connect, fail loudly, apply the
     * driver's defaults, then run the project's own queries.
     */
    private function connect(): PDO
    {
        $options = $this->settings->getDatabaseOptions($this->driver);

        $pdo = new PDO(
            $this->settings->getDatabaseDsn($this->driver),
            $this->credential($options, 'username'),
            $this->credential($options, 'password')
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->applyDriverDefaults($pdo, $options);
        $this->runInitialQueries($pdo);

        return $pdo;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function credential(array $options, string $key): ?string
    {
        $value = $options[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    private function executeFile(string $path): void
    {
        if (!file_exists($path)) {
            throw new SchemaFileNotFound($path);
        }

        $sql = (string) file_get_contents($path);

        foreach (StatementSplitter::split($sql) as $statement) {
            $this->getPdo()->exec($statement);
        }
    }

    /**
     * @return array{0: string, 1: array<string, string|null>}
     */
    private function existenceQuery(Dialect $dialect, string $table): array
    {
        $segments = explode('.', $table);
        $name     = (string) end($segments);
        $schema   = count($segments) > 1 ? $segments[0] : null;

        return match ($dialect) {
            Dialect::Mysql => [
                'SELECT COUNT(*) FROM information_schema.TABLES '
                . 'WHERE TABLE_SCHEMA = IFNULL(:schema, DATABASE()) AND TABLE_NAME = :name',
                [':schema' => $schema, ':name' => $name],
            ],
            // to_regclass() resolves search_path for an unqualified name and
            // parses a quoted qualified one, so one query covers both.
            Dialect::Pgsql => [
                'SELECT CASE WHEN to_regclass(:name) IS NULL THEN 0 ELSE 1 END',
                [':name' => $dialect->quoteIdentifier($table)],
            ],
            Dialect::Sqlite => [
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = :name",
                [':name' => $name],
            ],
        };
    }

    private function loadManifest(string $directory): void
    {
        $manifest = SchemaManifest::fromDirectory($directory);

        $this->executeFile($manifest->getPreSchemaFile());
        foreach ($manifest->getTables() as $table) {
            $this->executeFile($manifest->getFile($table));
        }
        $this->executeFile($manifest->getPostSchemaFile());

        $this->manifest = $manifest;
    }

    private function runInitialQueries(PDO $pdo): void
    {
        $queries = $this->settings->get('initial_queries', '');
        if (!is_string($queries) || $queries === '') {
            return;
        }

        $pdo->exec($queries);
    }
}
