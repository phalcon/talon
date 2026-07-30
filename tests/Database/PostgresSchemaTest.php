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

use Phalcon\Talon\Database\Connection;
use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;

final class PostgresSchemaTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->getDriver() !== 'pgsql') {
            $this->markTestSkipped('search_path applies to PostgreSQL only');
        }

        Talon::useSettings(Settings::fromEnv());
        self::resetConnections();

        $this->getConnection()->execute('CREATE SCHEMA IF NOT EXISTS talon_alt');
        $this->getConnection()->execute('DROP TABLE IF EXISTS talon_alt.widgets');
        $this->getConnection()->execute('CREATE TABLE talon_alt.widgets (id INTEGER)');
        $this->getConnection()->execute('INSERT INTO talon_alt.widgets VALUES (7)');
    }

    protected function tearDown(): void
    {
        if ($this->getDriver() === 'pgsql') {
            $this->getConnection()->execute('DROP SCHEMA IF EXISTS talon_alt CASCADE');
        }

        Talon::reset();

        parent::tearDown();
    }

    public function testSearchPathResolvesUnqualifiedNames(): void
    {
        $settings = Settings::fromEnv(['DATA_POSTGRES_SCHEMA' => 'talon_alt']);
        $scoped   = new Connection($settings, 'pgsql');

        $rows = $scoped->select('widgets');

        $this->assertCount(1, $rows);
        $this->assertEquals(7, $rows[0]['id']);
    }
}
