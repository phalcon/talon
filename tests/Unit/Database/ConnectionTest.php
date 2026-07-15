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
use Phalcon\Talon\Exceptions\SchemaFileNotFound;
use Phalcon\Talon\Settings;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    public function testExecuteAndSelect(): void
    {
        $conn = $this->sqlite();
        $conn->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT)');
        $conn->execute("INSERT INTO users VALUES (1, 'john.connor@skynet.dev')");

        $rows = $conn->select('users', ['id' => 1]);

        $this->assertCount(1, $rows);
        $this->assertSame('john.connor@skynet.dev', $rows[0]['email']);
    }

    public function testInitialQueriesRunAfterConnect(): void
    {
        $settings = Settings::fromArray([
            'root'            => '/app',
            'db'              => ['sqlite' => ['dbname' => ':memory:']],
            'initial_queries' => 'CREATE TABLE seeded (id INTEGER);',
        ]);

        $conn = new Connection($settings, 'sqlite');
        $conn->execute('INSERT INTO seeded VALUES (1)');

        $this->assertCount(1, $conn->select('seeded'));
    }

    public function testLoadSchemaFromFile(): void
    {
        $conn = $this->sqlite();
        $conn->loadSchema(dirname(__DIR__, 3) . '/resources/schema/sqlite.sql');
        $conn->execute("INSERT INTO users VALUES (2, 'a@b.c')");

        $this->assertCount(1, $conn->select('users', ['email' => 'a@b.c']));
    }

    public function testLoadSchemaWithUnreadableFileLoadsNothing(): void
    {
        // file_exists() passes but file_get_contents() returns false; the
        // (string) cast must turn that into "no statements", not a TypeError.
        $file = dirname(__DIR__, 3) . '/tests/_output/unreadable-' . uniqid() . '.sql';
        file_put_contents($file, 'CREATE TABLE hidden (id INTEGER);');
        chmod($file, 0o000);

        $conn = $this->sqlite();

        try {
            @$conn->loadSchema($file);

            $this->assertSame([], $conn->select('sqlite_master'));
        } finally {
            chmod($file, 0o644);
            unlink($file);
        }
    }

    public function testMissingSchemaFileThrows(): void
    {
        $this->expectException(SchemaFileNotFound::class);
        $this->sqlite()->loadSchema('/does/not/exist.sql');
    }

    public function testNonStringCredentialsAreIgnored(): void
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => [
                'sqlite' => [
                    'dbname'   => ':memory:',
                    'username' => ['not-a-string'],
                    'password' => ['not-a-string'],
                ],
            ],
        ]);

        $conn = new Connection($settings, 'sqlite');

        $this->assertSame([], $conn->select('sqlite_master'));
    }

    public function testSelectReturnsAllMatchingRows(): void
    {
        $conn = $this->sqlite();
        $conn->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT)');
        $conn->execute("INSERT INTO users VALUES (1, 'a@b.c')");
        $conn->execute("INSERT INTO users VALUES (2, 'a@b.c')");

        $this->assertCount(2, $conn->select('users', ['email' => 'a@b.c']));
    }

    public function testSqliteWalPragmaApplied(): void
    {
        // WAL mode requires a real file - sqlite silently falls back to
        // 'memory' journal mode for ':memory:' databases, so this needs a
        // file-backed connection to actually verify the pragma took effect.
        $root   = dirname(__DIR__, 3);
        $dbFile = Settings::fromArray(['root' => $root])
            ->outputPath('wal-pragma-' . uniqid('', true) . '.sqlite');

        try {
            $settings = Settings::fromArray([
                'root' => $root,
                'db'   => ['sqlite' => ['dbname' => $dbFile]],
            ]);

            $conn      = new Connection($settings, 'sqlite');
            $statement = $conn->getPdo()->query('PRAGMA journal_mode');
            $this->assertNotFalse($statement);

            $this->assertSame('wal', $statement->fetchColumn());
        } finally {
            unlink($dbFile);
        }
    }

    private function sqlite(): Connection
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]);

        return new Connection($settings, 'sqlite');
    }
}
