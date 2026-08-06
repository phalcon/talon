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

use function file_put_contents;
use function implode;
use function unlink;

final class SchemaLoadingTest extends AbstractDatabaseTestCase
{
    private string $dumpFile = '';

    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv());
        self::resetConnections();

        $this->dumpFile = $this->getSettings()->outputPath('schema-loading.sql');
    }

    protected function tearDown(): void
    {
        if ($this->dumpFile !== '') {
            @unlink($this->dumpFile);
        }

        $this->getConnection()->execute('DROP TABLE IF EXISTS widgets');

        Talon::reset();

        parent::tearDown();
    }

    public function testLoadSchemaExecutesEveryStatement(): void
    {
        // Creation statements only - the drop is the fixture's own run-time
        // half, exactly as the generator prepends one for a real dump.
        $fixture = new class ($this->getConnection()->getPdo(), false) extends AbstractSchema {
            protected string $table = 'widgets';

            protected function getStatementsMysql(): array
            {
                return [
                    'CREATE TABLE widgets (id INT PRIMARY KEY, label VARCHAR(64)) '
                    . 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;',
                    "INSERT INTO widgets VALUES (1, 'first');",
                    "INSERT INTO widgets VALUES (2, 'second');",
                ];
            }

            protected function getStatementsPgsql(): array
            {
                return [
                    'CREATE TABLE widgets (id INTEGER PRIMARY KEY, label VARCHAR(64));',
                    "INSERT INTO widgets VALUES (1, 'first');",
                    "INSERT INTO widgets VALUES (2, 'second');",
                ];
            }

            protected function getStatementsSqlite(): array
            {
                return [
                    'CREATE TABLE widgets (id INTEGER PRIMARY KEY, label TEXT);',
                    "INSERT INTO widgets VALUES (1, 'first');",
                    "INSERT INTO widgets VALUES (2, 'second');",
                ];
            }
        };

        $fixture->drop();

        file_put_contents(
            $this->dumpFile,
            implode("\n", $fixture->getStatements($this->getDialect()))
        );

        $this->getConnection()->loadSchema($this->dumpFile);

        $this->assertCount(2, $this->getFromDatabase('widgets'));
        $this->assertInDatabase('widgets', ['label' => 'second']);
    }
}
