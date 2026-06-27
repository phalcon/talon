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

namespace Phalcon\Talon\Tests\Unit;

use Phalcon\Talon\Contracts\Settings as SettingsContract;
use Phalcon\Talon\Exceptions\InvalidConfiguration;
use Phalcon\Talon\Exceptions\UnknownDriver;
use Phalcon\Talon\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testFromArrayImplementsContractAndResolvesPaths(): void
    {
        $settings = Settings::fromArray(['root' => '/app']);

        $this->assertInstanceOf(SettingsContract::class, $settings);
        $this->assertSame('/app', $settings->path());
        $this->assertSame('/app/tests/_output', $settings->path('tests/_output'));
    }

    public function testFromArrayRequiresRoot(): void
    {
        $this->expectException(InvalidConfiguration::class);
        Settings::fromArray([]);
    }

    public function testSqliteDsn(): void
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]);

        $this->assertSame('sqlite::memory:', $settings->getDatabaseDsn('sqlite'));
    }

    public function testMysqlDsnAndOptions(): void
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => ['mysql' => [
                'host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'talon',
                'username' => 'root', 'password' => 'secret', 'charset' => 'utf8mb4',
            ]],
        ]);

        $this->assertSame(
            'mysql:host=127.0.0.1;port=3306;dbname=talon;charset=utf8mb4',
            $settings->getDatabaseDsn('mysql')
        );
        $this->assertSame('root', $settings->getDatabaseOptions('mysql')['username']);
    }

    public function testUnknownDriverThrows(): void
    {
        $this->expectException(UnknownDriver::class);
        Settings::fromArray(['root' => '/app'])->getDatabaseDsn('oracle');
    }

    public function testFromEnvReadsOverrides(): void
    {
        $settings = Settings::fromEnv([
            'root'            => '/app',
            'DATA_REDIS_HOST' => 'redis-host',
        ]);

        $this->assertSame('redis-host', $settings->getRedisOptions()['host']);
    }
}
