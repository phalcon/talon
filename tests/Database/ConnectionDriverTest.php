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

use PDO;
use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;

final class ConnectionDriverTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv());
        self::resetConnections();
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testDialectMatchesTheConfiguredDriver(): void
    {
        $expected = match ($this->getDriver()) {
            'mariadb', 'mysql' => Dialect::Mysql,
            'pgsql'            => Dialect::Pgsql,
            default            => Dialect::Sqlite,
        };

        $this->assertSame($expected, $this->getDialect());
    }

    public function testMariadbReportsTheMysqlDialect(): void
    {
        if ($this->getDriver() !== 'mariadb') {
            $this->markTestSkipped('MariaDB only');
        }

        $version = $this->getConnection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);

        $this->assertIsString($version);
        $this->assertStringContainsString('MariaDB', $version);
        $this->assertSame(Dialect::Mysql, $this->getDialect());
    }

    public function testTheDialectValueIsThePdoDriverName(): void
    {
        $this->assertSame(
            $this->getDialect()->value,
            $this->getConnection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)
        );
    }
}
