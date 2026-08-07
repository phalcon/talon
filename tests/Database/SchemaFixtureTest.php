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

namespace Phalcon\Talon\Tests\Database;

use Phalcon\Talon\Database\Schema\AbstractSchema;
use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use Phalcon\Talon\Tests\Database\Fixtures\UsersSchema;

use function is_string;

/**
 * Drives the fixture lifecycle against a real server, so the per-dialect
 * branches - MySQL's FK-check dance in clear(), Postgres' sequence catch-up -
 * are exercised by the driver suite that owns them.
 */
final class SchemaFixtureTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv());
        self::resetConnections();

        // The unit suite runs this directory with driver=sqlite and no
        // dump_file, so load the manifest explicitly rather than relying on
        // the per-driver config to have set one.
        $dumpFile = $this->getSettings()->get('dump_file');
        $this->getConnection()->loadSchema(
            $this->getSettings()->rootPath(
                is_string($dumpFile) && $dumpFile !== '' ? $dumpFile : 'resources/schema/sqlite'
            )
        );
    }

    protected function tearDown(): void
    {
        $this->getConnection()->execute('DROP TABLE IF EXISTS talon_sequences');

        Talon::reset();

        parent::tearDown();
    }

    public function testAddTableRebuildsOneTableFromTheManifest(): void
    {
        $this->getConnection()->execute('DROP TABLE IF EXISTS users');
        $this->assertFalse($this->getConnection()->tableExists('users'));

        $this->addTable('users');

        $this->assertTrue($this->getConnection()->tableExists('users'));
    }

    public function testAdvanceSequenceKeepsExplicitIdsUsable(): void
    {
        // Declared inline rather than behind a helper: a helper would have to
        // return AbstractSchema, and phpstan cannot see the extra methods of
        // an anonymous class through that type.
        $schema = new class ($this->getConnection()->getPdo(), false) extends AbstractSchema {
            protected string $table = 'talon_sequences';

            public function seed(int $id, string $name): int
            {
                $result = $this->execute(
                    'INSERT INTO talon_sequences (id, name) VALUES (:id, :name)',
                    [':id' => $id, ':name' => $name]
                );

                $this->advanceSequence('id', $id);

                return $result;
            }

            public function seedWithoutId(string $name): int
            {
                return $this->execute(
                    'INSERT INTO talon_sequences (name) VALUES (:name)',
                    [':name' => $name]
                );
            }

            protected function getStatementsMysql(): array
            {
                return [
                    'CREATE TABLE talon_sequences ('
                    . 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL);',
                ];
            }

            protected function getStatementsPgsql(): array
            {
                return [
                    'CREATE TABLE talon_sequences ('
                    . 'id SERIAL PRIMARY KEY, name VARCHAR(50) NOT NULL);',
                ];
            }

            protected function getStatementsSqlite(): array
            {
                return [
                    'CREATE TABLE talon_sequences ('
                    . 'id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL);',
                ];
            }
        };
        $schema->create();

        // Runs the setval() on Postgres and returns immediately everywhere
        // else, so both branches are covered across the driver suites.
        $schema->seed(10, 'explicit');

        $this->assertCount(1, $this->getFromDatabase('talon_sequences', ['id' => 10]));

        // A serial that never advanced would collide with id 10 here.
        $schema->seedWithoutId('generated');

        $this->assertCount(2, $this->getFromDatabase('talon_sequences'));
    }

    public function testClearRemovesEveryRow(): void
    {
        $schema = new UsersSchema($this->getConnection()->getPdo(), false);
        $schema->insert(1, 'sarah.connor@skynet.dev');

        // The return value is not portable: TRUNCATE reports no affected rows
        // on MySQL and Postgres, so only SQLite's DELETE returns a count. The
        // table being empty afterwards is the part that holds everywhere.
        $schema->clear();

        $this->assertSame([], $this->getFromDatabase('users'));
    }
}
