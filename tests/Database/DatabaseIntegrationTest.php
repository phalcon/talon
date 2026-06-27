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

use function getenv;

/**
 * Driver-agnostic: runs against sqlite, mysql, or pgsql depending on the `driver`
 * env set by the chosen phpunit config.
 */
final class DatabaseIntegrationTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv(['root' => '/app']));
        self::resetConnections();

        $driver = getenv('driver') ?: 'sqlite';
        $this->getConnection()->loadSchema('/app/resources/schema/' . $driver . '.sql');
        $this->getConnection()->execute("INSERT INTO users (id, email) VALUES (1, 'nikos@niden.net')");
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testRowIsInDatabase(): void
    {
        $this->assertInDatabase('users', ['id' => 1]);
    }

    public function testRowIsNotInDatabase(): void
    {
        $this->assertNotInDatabase('users', ['id' => 999]);
    }
}
