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

use PDO;
use Phalcon\Talon\Database\Dialect;
use Phalcon\Talon\Exceptions\UnknownDriver;
use PHPUnit\Framework\TestCase;

final class DialectTest extends TestCase
{
    public function testFromPdoMapsDriverNames(): void
    {
        $this->assertSame(Dialect::Mysql, Dialect::fromPdo($this->pdoReporting('mysql')));
        $this->assertSame(Dialect::Pgsql, Dialect::fromPdo($this->pdoReporting('pgsql')));
        $this->assertSame(Dialect::Sqlite, Dialect::fromPdo($this->pdoReporting('sqlite')));
    }

    public function testFromPdoThrowsForUnknownDriver(): void
    {
        $this->expectException(UnknownDriver::class);
        $this->expectExceptionMessage("Unknown database driver 'oci'");

        Dialect::fromPdo($this->pdoReporting('oci'));
    }

    public function testQuoteIdentifierEscapesTheDelimiter(): void
    {
        $this->assertSame('`we``ird`', Dialect::Mysql->quoteIdentifier('we`ird'));
        $this->assertSame('"we""ird"', Dialect::Pgsql->quoteIdentifier('we"ird'));
        $this->assertSame('"we""ird"', Dialect::Sqlite->quoteIdentifier('we"ird'));
    }

    public function testQuoteIdentifierQuotesEachSegmentOfAQualifiedName(): void
    {
        $this->assertSame('`private`.`users`', Dialect::Mysql->quoteIdentifier('private.users'));
        $this->assertSame('"private"."users"', Dialect::Pgsql->quoteIdentifier('private.users'));
    }

    public function testQuoteIdentifierWrapsInTheDialectDelimiter(): void
    {
        $this->assertSame('`order`', Dialect::Mysql->quoteIdentifier('order'));
        $this->assertSame('"order"', Dialect::Pgsql->quoteIdentifier('order'));
        $this->assertSame('"order"', Dialect::Sqlite->quoteIdentifier('order'));
    }

    private function pdoReporting(string $driverName): PDO
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn($driverName);

        return $pdo;
    }
}
