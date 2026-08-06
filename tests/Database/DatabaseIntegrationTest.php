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

use function is_string;

/**
 * Driver-agnostic: runs against sqlite, mysql, mariadb, or pgsql depending on the
 * `driver` env set by the chosen phpunit config. The schema comes from that
 * config's `dump_file`, so a suite can point at any schema it likes.
 */
final class DatabaseIntegrationTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv());
        self::resetConnections();

        $dumpFile = $this->getSettings()->get('dump_file');
        $this->getConnection()->loadSchema(
            $this->getSettings()->rootPath(
                is_string($dumpFile) && $dumpFile !== '' ? $dumpFile : 'resources/schema/sqlite'
            )
        );
        $this->getConnection()->execute("INSERT INTO users (id, email) VALUES (1, 'john.connor@skynet.dev')");
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
