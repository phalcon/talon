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

use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use Phalcon\Talon\Tests\Database\Fixtures\AbstractDriverSchema;

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
        $fixture = new class extends AbstractDriverSchema {
            protected function sqlMysql(): array
            {
                return [
                    'DROP TABLE IF EXISTS widgets;',
                    'CREATE TABLE widgets (id INT PRIMARY KEY, label VARCHAR(64)) '
                    . 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;',
                    "INSERT INTO widgets VALUES (1, 'first');",
                    "INSERT INTO widgets VALUES (2, 'second');",
                ];
            }

            protected function sqlPgsql(): array
            {
                return [
                    'DROP TABLE IF EXISTS widgets;',
                    'CREATE TABLE widgets (id INTEGER PRIMARY KEY, label VARCHAR(64));',
                    "INSERT INTO widgets VALUES (1, 'first');",
                    "INSERT INTO widgets VALUES (2, 'second');",
                ];
            }

            protected function sqlSqlite(): array
            {
                return [
                    'DROP TABLE IF EXISTS widgets;',
                    'CREATE TABLE widgets (id INTEGER PRIMARY KEY, label TEXT);',
                    "INSERT INTO widgets VALUES (1, 'first');",
                    "INSERT INTO widgets VALUES (2, 'second');",
                ];
            }
        };

        file_put_contents($this->dumpFile, implode("\n", $fixture->sqlFor($this->getDialect())));

        $this->getConnection()->loadSchema($this->dumpFile);

        $this->assertCount(2, $this->getFromDatabase('widgets'));
        $this->assertInDatabase('widgets', ['label' => 'second']);
    }
}
