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

namespace Phalcon\Talon\Tests\Unit\Database\Schema;

use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Database\Schema\SchemaCollector;
use Phalcon\Talon\Database\Schema\SchemaGenerator;
use Phalcon\Talon\Database\Schema\SchemaManifest;
use Phalcon\Talon\Database\Schema\SchemaWriter;
use Phalcon\Talon\Exceptions\SchemaTableDuplicate;
use Phalcon\Talon\Traits\FileSystemTrait;
use PHPUnit\Framework\TestCase;

use function array_map;
use function basename;
use function dirname;
use function file_get_contents;
use function glob;
use function rmdir;
use function sort;

final class SchemaWriterTest extends TestCase
{
    use FileSystemTrait;

    private const FIXTURE_NAMESPACE = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

    private string $root = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = dirname(__DIR__, 3) . '/_output/schema-writer';
    }

    protected function tearDown(): void
    {
        // Mutation runs leave debris under misspelled paths, including
        // dotfiles that glob() will not match. safeDeleteDirectory() walks
        // the tree itself, so nothing survives to make rmdir() warn.
        $this->safeDeleteDirectory($this->root);
        parent::tearDown();
    }

    public function testAbsentTableGetsNoFileAndNoManifestEntry(): void
    {
        $directory = $this->writer()->write(Dialect::Sqlite, $this->root);

        $this->assertFileDoesNotExist($directory . '/albums.sql');
        $this->assertSame(
            ['widgets', 'zones'],
            SchemaManifest::fromDirectory($directory)->getTables()
        );
    }

    public function testDuplicateTableNamesAreRefused(): void
    {
        $collector = new SchemaCollector(
            dirname(__DIR__, 3) . '/Fixtures/Duplicate',
            'Phalcon\\Talon\\Tests\\Fixtures\\Duplicate'
        );
        $writer    = new SchemaWriter($collector, new SchemaGenerator($collector));

        $this->expectException(SchemaTableDuplicate::class);
        $this->expectExceptionMessage(
            "Two schema definitions both declare the table 'widgets' for the 'sqlite' dialect. "
            . 'Table names are the artifact key, so the second would overwrite the first.'
        );

        $writer->write(Dialect::Sqlite, $this->root);
    }

    public function testEmptyPreAndPostAreStillWritten(): void
    {
        $directory = $this->writer()->write(Dialect::Sqlite, $this->root);

        $this->assertFileExists($directory . '/' . SchemaManifest::PRE_SCHEMA);
        $this->assertFileExists($directory . '/' . SchemaManifest::POST_SCHEMA);
        $this->assertSame('', (string) file_get_contents($directory . '/' . SchemaManifest::PRE_SCHEMA));
        $this->assertSame('', (string) file_get_contents($directory . '/' . SchemaManifest::POST_SCHEMA));
    }

    public function testManifestRecordsDependencies(): void
    {
        $directory = $this->writer()->write(Dialect::Mysql, $this->root);
        $manifest  = SchemaManifest::fromDirectory($directory);

        $this->assertSame(['albums', 'widgets', 'zones'], $manifest->getTables());
        $this->assertSame(['widgets'], $manifest->getDependencies('zones'));
        $this->assertSame([], $manifest->getDependencies('widgets'));
    }

    public function testTableFileCarriesItsOwnDrop(): void
    {
        $directory = $this->writer()->write(Dialect::Sqlite, $this->root);

        $this->assertSame(
            "DROP TABLE IF EXISTS \"widgets\";\n\n"
            . "CREATE TABLE widgets (id INTEGER PRIMARY KEY, label TEXT);\n",
            (string) file_get_contents($directory . '/widgets.sql')
        );
    }

    public function testWritesTheExpectedFileSetForADialect(): void
    {
        $directory = $this->writer()->write(Dialect::Mysql, $this->root);

        $names = array_map(
            static fn (string $path): string => basename($path),
            glob($directory . '/*') ?: []
        );
        sort($names);

        $this->assertSame(
            [
                '_postSchema.sql',
                '_preSchema.sql',
                'albums.sql',
                'manifest.json',
                'widgets.sql',
                'zones.sql',
            ],
            $names
        );
    }

    private function writer(): SchemaWriter
    {
        $collector = new SchemaCollector(
            dirname(__DIR__, 3) . '/Fixtures/Schema',
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\PreSchema',
            self::FIXTURE_NAMESPACE . '\\PostSchema'
        );

        return new SchemaWriter($collector, new SchemaGenerator($collector));
    }
}
