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
        $this->assertSame('/app', $settings->rootPath());
        $this->assertSame('/app/sub/file.txt', $settings->rootPath('sub/file.txt'));
    }

    public function testDirectoryAccessorsUseDefaults(): void
    {
        $settings = Settings::fromArray(['root' => '/app']);

        $this->assertSame('/app/tests', $settings->testsPath());
        $this->assertSame('/app/tests/_data', $settings->dataPath());
        $this->assertSame('/app/tests/_output', $settings->outputPath());
        $this->assertSame('/app/tests/_output/cache', $settings->cachePath());
        $this->assertSame('/app/tests/_output/logs', $settings->logsPath());
        $this->assertSame('/app/tests/support', $settings->supportPath());
        $this->assertSame('/app/tests/_output/run.log', $settings->outputPath('run.log'));
    }

    public function testDirectoryAccessorsHonorOverrides(): void
    {
        $settings = Settings::fromArray([
            'root'  => '/app',
            'paths' => ['output' => 'build/out'],
        ]);

        $this->assertSame('/app/build/out', $settings->outputPath());
        $this->assertSame('/app/tests/_data', $settings->dataPath());
    }

    public function testFromEnvDiscoversRootFromComposerJson(): void
    {
        // The package ships a composer.json at its root; discovery must find it.
        $settings = Settings::fromEnv();

        $this->assertFileExists($settings->rootPath('composer.json'));
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
