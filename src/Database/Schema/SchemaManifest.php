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

use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Exceptions\SchemaManifestNotFound;
use Phalcon\Talon\Exceptions\SchemaTableNotFound;

use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function rtrim;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The one reader and writer of manifest.json. Filenames carry "always first"
 * and "always last" on their own, so the manifest records the middle only:
 * which tables exist in this dialect, in what order, and what each requires.
 */
final class SchemaManifest
{
    public const FILE        = 'manifest.json';
    public const POST_SCHEMA = '_postSchema.sql';
    public const PRE_SCHEMA  = '_preSchema.sql';

    /**
     * @param array<string, list<string>> $dependencies
     * @param array<string, string>       $files
     * @param list<string>                $tables
     */
    private function __construct(
        private readonly string $directory,
        private readonly array $tables,
        private readonly array $files,
        private readonly array $dependencies,
    ) {
    }

    /**
     * @param list<SchemaDefinition> $definitions present in this dialect, in load order
     */
    public static function encode(Dialect $dialect, array $definitions): string
    {
        $tables = [];
        foreach ($definitions as $definition) {
            $table = $definition->getTable();

            $tables[] = [
                'table'        => $table,
                'file'         => $table . '.sql',
                'dependencies' => $definition->getDependencies(),
            ];
        }

        $json = json_encode(
            ['dialect' => $dialect->value, 'tables' => $tables],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return ($json === false ? '{}' : $json) . "\n";
    }

    public static function fromDirectory(string $directory): self
    {
        $directory = rtrim($directory, '/');
        $path      = $directory . DIRECTORY_SEPARATOR . self::FILE;

        if (!is_file($path)) {
            throw new SchemaManifestNotFound($path);
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) file_get_contents($path), true);
        $rows    = is_array($decoded) && is_array($decoded['tables'] ?? null)
            ? $decoded['tables']
            : [];

        $tables       = [];
        $files        = [];
        $dependencies = [];

        /** @var mixed $row */
        foreach ($rows as $row) {
            if (!is_array($row) || !is_string($row['table'] ?? null)) {
                continue;
            }

            $table = $row['table'];
            $file  = is_string($row['file'] ?? null) ? $row['file'] : $table . '.sql';

            $tables[]             = $table;
            $files[$table]        = $file;
            $dependencies[$table] = self::stringList($row['dependencies'] ?? null);
        }

        return new self($directory, $tables, $files, $dependencies);
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        /** @var mixed $item */
        foreach ($value as $item) {
            if (is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getDependencies(string $table): array
    {
        return $this->dependencies[$table] ?? throw new SchemaTableNotFound($table);
    }

    public function getFile(string $table): string
    {
        $file = $this->files[$table] ?? throw new SchemaTableNotFound($table);

        return $this->directory . DIRECTORY_SEPARATOR . $file;
    }

    public function getPostSchemaFile(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . self::POST_SCHEMA;
    }

    public function getPreSchemaFile(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . self::PRE_SCHEMA;
    }

    /**
     * @return list<string>
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
