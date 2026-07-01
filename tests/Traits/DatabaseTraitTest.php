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

namespace Phalcon\Talon\Tests\Traits;

use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use Phalcon\Talon\Traits\DatabaseTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

use function dirname;

final class DatabaseTraitTest extends TestCase
{
    use DatabaseTrait;

    protected function setUp(): void
    {
        Talon::useSettings(Settings::fromArray([
            'root' => '/app',
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]));
        self::resetConnections();

        $this->getConnection()->execute('CREATE TABLE users (id INTEGER, email TEXT)');
        $this->getConnection()->execute("INSERT INTO users VALUES (1, 'a@b.c')");
    }

    protected function tearDown(): void
    {
        self::resetConnections();
        Talon::reset();
        parent::tearDown();
    }

    public function testAssertInDatabasePasses(): void
    {
        $this->assertInDatabase('users', ['id' => 1]);
    }

    public function testAssertInDatabaseFailsWhenMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->assertInDatabase('users', ['id' => 999]);
    }

    public function testAssertNotInDatabasePasses(): void
    {
        $this->assertNotInDatabase('users', ['id' => 999]);
    }

    public function testGetConnectionLoadsSchemaOnceWhenDumpFileConfigured(): void
    {
        Talon::useSettings(Settings::fromArray([
            'root'      => '/app',
            'db'        => ['sqlite' => ['dbname' => ':memory:']],
            'dump_file' => dirname(__DIR__) . '/Fakes/seeded-users.sql',
        ]));
        self::resetConnections();

        $this->assertInDatabase('seeded_users', ['id' => 1]);
    }

    public function testGetDriverReturnsCurrentEnvDriver(): void
    {
        putenv('driver=pgsql');

        try {
            $this->assertSame('pgsql', $this->getDriver());
        } finally {
            putenv('driver');
        }
    }
}
