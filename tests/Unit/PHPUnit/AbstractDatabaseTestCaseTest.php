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

namespace Phalcon\Talon\Tests\Unit\PHPUnit;

use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;

final class AbstractDatabaseTestCaseTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromArray([
            'root' => '/app',
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]));
        self::resetConnections();

        $this->getConnection()->execute('CREATE TABLE t (id INTEGER)');
        $this->getConnection()->execute('INSERT INTO t VALUES (1)');
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testAssertInDatabase(): void
    {
        $this->assertInDatabase('t', ['id' => 1]);
    }

    public function testTearDownResetsConnections(): void
    {
        $first = $this->getConnection();

        parent::tearDown();

        $this->assertNotSame($first, $this->getConnection());
    }
}
