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

namespace Phalcon\Talon\Tests\Unit\Database;

use Phalcon\Talon\Database\Connection;
use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Database\Schema\SchemaCollector;
use Phalcon\Talon\Database\Schema\SchemaGenerator;
use Phalcon\Talon\Database\Schema\SchemaManifest;
use Phalcon\Talon\Database\Schema\SchemaWriter;
use Phalcon\Talon\Exceptions\SchemaDependencyMissing;
use Phalcon\Talon\Exceptions\SchemaManifestNotFound;
use Phalcon\Talon\Exceptions\SchemaManifestNotLoaded;
use Phalcon\Talon\Exceptions\SchemaTableNotFound;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Traits\FileSystemTrait;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_put_contents;
use function glob;
use function is_dir;
use function mkdir;
use function rmdir;

final class ConnectionManifestTest extends TestCase
{
    use FileSystemTrait;

    private const FIXTURE_NAMESPACE = 'Phalcon\\Talon\\Tests\\Fixtures\\Schema';

    private string $root = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = dirname(__DIR__, 2) . '/_output/connection-manifest';
    }

    protected function tearDown(): void
    {
        // Mutation runs leave debris under misspelled paths, including
        // dotfiles that glob() will not match. safeDeleteDirectory() walks
        // the tree itself, so nothing survives to make rmdir() warn.
        $this->safeDeleteDirectory($this->root);
        parent::tearDown();
    }

    public function testAddTableBeforeLoadingThrows(): void
    {
        $this->expectException(SchemaManifestNotLoaded::class);
        $this->expectExceptionMessage(
            "Cannot add table 'widgets': no schema manifest has been loaded"
        );

        $this->connection()->addTable('widgets');
    }

    public function testAddTableRecreatesASingleTable(): void
    {
        $connection = $this->connection();
        $connection->loadSchema($this->write());

        $connection->execute("INSERT INTO widgets (id, label) VALUES (1, 'first')");
        $connection->execute('DROP TABLE widgets');

        $connection->addTable('widgets');

        $this->assertTrue($connection->tableExists('widgets'));
        $this->assertSame([], $connection->select('widgets'));
    }

    public function testAddTableRefusesWhenADependencyIsMissing(): void
    {
        $connection = $this->connection();
        $connection->loadSchema($this->write());

        $connection->execute('DROP TABLE zones');
        $connection->execute('DROP TABLE widgets');

        $this->expectException(SchemaDependencyMissing::class);
        $this->expectExceptionMessage(
            "Table 'zones' requires 'widgets', which does not exist. "
            . "Create it first with addTable('widgets')."
        );

        $connection->addTable('zones');
    }

    public function testAddTableSucceedsOnceTheDependencyIsSatisfied(): void
    {
        $connection = $this->connection();
        $connection->loadSchema($this->write());

        $connection->execute('DROP TABLE zones');
        $connection->execute('DROP TABLE widgets');

        $connection->addTable('widgets');
        $connection->addTable('zones');

        $this->assertTrue($connection->tableExists('zones'));
    }

    public function testAddTableUnknownToTheManifestThrows(): void
    {
        $connection = $this->connection();
        $connection->loadSchema($this->write());

        $this->expectException(SchemaTableNotFound::class);

        $connection->addTable('nope');
    }

    public function testDirectoryWithoutAManifestThrows(): void
    {
        $empty = $this->root . '/sqlite';
        if (!is_dir($empty)) {
            mkdir($empty, 0777, true);
        }

        $this->expectException(SchemaManifestNotFound::class);

        $this->connection()->loadSchema($empty);
    }

    public function testLoadSchemaCreatesEveryManifestTable(): void
    {
        $connection = $this->connection();
        $connection->loadSchema($this->write());

        $this->assertSame([], $connection->select('widgets'));
        $this->assertSame([], $connection->select('zones'));
    }

    public function testLoadSchemaIsIdempotent(): void
    {
        $connection = $this->connection();
        $directory  = $this->write();

        $connection->loadSchema($directory);
        $connection->execute("INSERT INTO widgets (id, label) VALUES (1, 'first')");
        $connection->loadSchema($directory);

        $this->assertSame([], $connection->select('widgets'));
    }

    public function testLoadSchemaRunsPreAndPostSchema(): void
    {
        $directory = $this->write();

        // Both bracket files are rewritten to leave a visible marker, so a
        // loader that skipped either one is caught rather than assumed.
        file_put_contents(
            $directory . '/' . SchemaManifest::PRE_SCHEMA,
            "CREATE TABLE pre_marker (id INTEGER);\n"
        );
        file_put_contents(
            $directory . '/' . SchemaManifest::POST_SCHEMA,
            "CREATE TABLE post_marker (id INTEGER);\n"
        );

        $connection = $this->connection();
        $connection->loadSchema($directory);

        $this->assertTrue($connection->tableExists('pre_marker'));
        $this->assertTrue($connection->tableExists('post_marker'));
    }

    public function testTableExistsReflectsTheDatabase(): void
    {
        $connection = $this->connection();

        $this->assertFalse($connection->tableExists('widgets'));

        $connection->loadSchema($this->write());

        $this->assertTrue($connection->tableExists('widgets'));
        $this->assertFalse($connection->tableExists('no_such_table'));
    }

    public function testTheFlatFilePathStillWorks(): void
    {
        if (!is_dir($this->root)) {
            mkdir($this->root, 0777, true);
        }

        $file      = $this->root . '/flat.sql';
        $collector = $this->collector();
        file_put_contents($file, (new SchemaGenerator($collector))->generate(Dialect::Sqlite));

        $connection = $this->connection();
        $connection->loadSchema($file);

        $this->assertSame([], $connection->select('widgets'));
    }

    private function collector(): SchemaCollector
    {
        return new SchemaCollector(
            dirname(__DIR__, 2) . '/Fixtures/Schema',
            self::FIXTURE_NAMESPACE,
            self::FIXTURE_NAMESPACE . '\\PreSchema',
            self::FIXTURE_NAMESPACE . '\\PostSchema'
        );
    }

    private function connection(): Connection
    {
        $settings = Settings::fromArray([
            'root' => dirname(__DIR__, 3),
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]);

        return new Connection($settings, 'sqlite');
    }

    private function write(): string
    {
        $collector = $this->collector();

        return (new SchemaWriter($collector, new SchemaGenerator($collector)))
            ->write(Dialect::Sqlite, $this->root);
    }
}
