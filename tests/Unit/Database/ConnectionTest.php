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

use Phalcon\Talon\Database\Connection;
use Phalcon\Talon\Exceptions\SchemaFileNotFound;
use Phalcon\Talon\Settings;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    public function testExecuteAndSelect(): void
    {
        $conn = $this->sqlite();
        $conn->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT)');
        $conn->execute("INSERT INTO users VALUES (1, 'nikos@niden.net')");

        $rows = $conn->select('users', ['id' => 1]);

        $this->assertCount(1, $rows);
        $this->assertSame('nikos@niden.net', $rows[0]['email']);
    }

    public function testLoadSchemaFromFile(): void
    {
        $conn = $this->sqlite();
        $conn->loadSchema(dirname(__DIR__, 3) . '/resources/schema/sqlite.sql');
        $conn->execute("INSERT INTO users VALUES (2, 'a@b.c')");

        $this->assertCount(1, $conn->select('users', ['email' => 'a@b.c']));
    }

    public function testMissingSchemaFileThrows(): void
    {
        $this->expectException(SchemaFileNotFound::class);
        $this->sqlite()->loadSchema('/does/not/exist.sql');
    }

    private function sqlite(): Connection
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]);

        return new Connection($settings, 'sqlite');
    }
}
