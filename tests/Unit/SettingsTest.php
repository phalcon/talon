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
use Phalcon\Talon\ServiceOptions;
use Phalcon\Talon\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    public function testDirectoryAccessorsHonorOverrides(): void
    {
        $settings = Settings::fromArray([
            'root'  => '/app',
            'paths' => ['output' => 'build/out'],
        ]);

        $this->assertSame('/app/build/out', $settings->outputPath());
        $this->assertSame('/app/tests/_data', $settings->dataPath());
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

    public function testDirJoinsOverrideAndRelativeWithoutDuplicateSlashes(): void
    {
        $settings = Settings::fromArray([
            'root'  => '/app',
            'paths' => ['output' => 'build/out/'],
        ]);

        $this->assertSame('/app/build/out/run.log', $settings->outputPath('/run.log'));
    }

    public function testDiscoverRootFallsBackWhenNoComposerJson(): void
    {
        $cwd = getcwd();

        try {
            chdir('/');
            $this->assertSame('/', Settings::fromEnv()->rootPath());
        } finally {
            chdir($cwd ?: '/srv');
        }
    }

    public function testDiscoverRootWalksUpToComposerJson(): void
    {
        $cwd = getcwd();

        try {
            // A nested directory with no composer.json of its own.
            chdir(__DIR__);
            $this->assertFileExists(Settings::fromEnv()->rootPath('composer.json'));
        } finally {
            chdir($cwd ?: '/srv');
        }
    }

    public function testFromArrayAcceptsServiceOptionsInstanceDirectly(): void
    {
        $settings = Settings::fromArray([
            'root'     => '/app',
            'services' => [
                'redis' => new ServiceOptions('redis', ['host' => 'direct-host']),
            ],
        ]);

        $this->assertSame(['host' => 'direct-host'], $settings->getServiceOptions('redis'));
    }

    public function testFromArrayCastsIntegerServiceKeys(): void
    {
        $settings = Settings::fromArray([
            'root'     => '/app',
            'services' => [0 => ['host' => 'indexed-host']],
        ]);

        $this->assertSame(['host' => 'indexed-host'], $settings->getServiceOptions('0'));
    }
    public function testFromArrayImplementsContractAndResolvesPaths(): void
    {
        $settings = Settings::fromArray(['root' => '/app']);

        $this->assertInstanceOf(SettingsContract::class, $settings);
        $this->assertSame('/app', $settings->rootPath());
        $this->assertSame('/app/sub/file.txt', $settings->rootPath('sub/file.txt'));
    }

    public function testFromArrayKeepsAllDbDrivers(): void
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => [
                'mysql'  => ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'talon'],
                'sqlite' => ['dbname' => '/data/db.sqlite'],
            ],
        ]);

        $this->assertSame('sqlite:/data/db.sqlite', $settings->getDatabaseDsn('sqlite'));
    }

    public function testFromArrayKeepsAllServices(): void
    {
        $settings = Settings::fromArray([
            'root'     => '/app',
            'services' => [
                'first'  => ['host' => 'one'],
                'second' => ['host' => 'two'],
            ],
        ]);

        $this->assertSame(['host' => 'one'], $settings->getServiceOptions('first'));
        $this->assertSame(['host' => 'two'], $settings->getServiceOptions('second'));
    }

    public function testFromArrayRequiresRoot(): void
    {
        $this->expectException(InvalidConfiguration::class);
        Settings::fromArray([]);
    }

    public function testFromEnvBuildsDbServiceOptionsFromOverrides(): void
    {
        $settings = Settings::fromEnv([
            'root'                 => '/app',
            'DATA_MYSQL_HOST'      => 'mysql-host',
            'DATA_MYSQL_PORT'      => 3307,
            'DATA_MYSQL_NAME'      => 'mysql-db',
            'DATA_MYSQL_USER'      => 'mysql-user',
            'DATA_MYSQL_PASS'      => 'mysql-pass',
            'DATA_MYSQL_CHARSET'   => 'utf8',
            'DATA_POSTGRES_HOST'   => 'pgsql-host',
            'DATA_POSTGRES_PORT'   => '5433',
            'DATA_POSTGRES_NAME'   => 'pgsql-db',
            'DATA_POSTGRES_USER'   => 'pgsql-user',
            'DATA_POSTGRES_PASS'   => 'pgsql-pass',
            'DATA_POSTGRES_SCHEMA' => 'pgsql-schema',
            'DATA_SQLITE_NAME'     => '/data/db.sqlite',
        ]);

        $this->assertSame(
            [
                'host'     => 'mysql-host',
                'port'     => 3307,
                'dbname'   => 'mysql-db',
                'username' => 'mysql-user',
                'password' => 'mysql-pass',
                'charset'  => 'utf8',
            ],
            $settings->getServiceOptions('mysql')
        );
        $this->assertSame(
            [
                'host'     => 'pgsql-host',
                'port'     => 5433,
                'dbname'   => 'pgsql-db',
                'username' => 'pgsql-user',
                'password' => 'pgsql-pass',
                'schema'   => 'pgsql-schema',
            ],
            $settings->getServiceOptions('pgsql')
        );
        $this->assertSame(['dbname' => '/data/db.sqlite'], $settings->getServiceOptions('sqlite'));
    }

    public function testFromEnvDiscoversRootFromComposerJson(): void
    {
        // The package ships a composer.json at its root; discovery must find it.
        $settings = Settings::fromEnv();

        $this->assertFileExists($settings->rootPath('composer.json'));
    }

    public function testFromEnvFallsBackToEnvSuperglobal(): void
    {
        $originalGetenv = getenv('DATA_MYSQL_NAME');
        $originalEnv    = $_ENV['DATA_MYSQL_NAME'] ?? null;

        try {
            putenv('DATA_MYSQL_NAME');
            $_ENV['DATA_MYSQL_NAME'] = 'env-db';

            $settings = Settings::fromEnv(['root' => '/app']);

            $this->assertSame('env-db', $settings->getServiceOptions('mysql')['dbname']);
        } finally {
            putenv(
                $originalGetenv === false
                    ? 'DATA_MYSQL_NAME'
                    : 'DATA_MYSQL_NAME=' . $originalGetenv
            );
            if ($originalEnv === null) {
                unset($_ENV['DATA_MYSQL_NAME']);
            } else {
                $_ENV['DATA_MYSQL_NAME'] = $originalEnv;
            }
        }
    }

    public function testFromEnvReadsBeanstalkServiceOptions(): void
    {
        $settings = Settings::fromEnv([
            'root'                  => '/app',
            'DATA_BEANSTALKD_HOST'  => 'beanstalk-host',
            'DATA_BEANSTALKD_PORT'  => '11300',
        ]);

        $this->assertSame(
            ['host' => 'beanstalk-host', 'port' => '11300'],
            $settings->getServiceOptions('beanstalk')
        );
    }

    public function testFromEnvReadsDumpFileAndInitialQueries(): void
    {
        $settings = Settings::fromEnv([
            'root'            => '/app',
            'dump_file'       => 'tests/support/assets/schema/mysql.sql',
            'initial_queries' => 'SET NAMES utf8;',
        ]);

        $this->assertSame('tests/support/assets/schema/mysql.sql', $settings->get('dump_file'));
        $this->assertSame('SET NAMES utf8;', $settings->get('initial_queries'));
    }

    public function testFromEnvReadsMemcachedOverrides(): void
    {
        $settings = Settings::fromEnv([
            'root'                => '/app',
            'DATA_MEMCACHED_HOST' => 'memcached-host',
        ]);

        $this->assertSame('memcached-host', $settings->getServiceOptions('memcached')['host']);
    }

    public function testFromEnvReadsOverrides(): void
    {
        $settings = Settings::fromEnv([
            'root'            => '/app',
            'DATA_REDIS_HOST' => 'redis-host',
        ]);

        $this->assertSame('redis-host', $settings->getServiceOptions('redis')['host']);
    }

    public function testFromEnvReadsRedisClusterOverrides(): void
    {
        $settings = Settings::fromEnv([
            'root'                     => '/app',
            'DATA_REDIS_CLUSTER_HOSTS' => '10.0.0.1:6379,10.0.0.2:6379',
            'DATA_REDIS_CLUSTER_AUTH'  => 'secret',
        ]);

        $this->assertSame(
            ['hosts' => ['10.0.0.1:6379', '10.0.0.2:6379'], 'auth' => 'secret'],
            $settings->getServiceOptions('redisCluster')
        );
    }

    public function testFromEnvRedisClusterHostsEmptyWhenUnset(): void
    {
        $settings = Settings::fromEnv(['root' => '/app']);

        $this->assertSame([], $settings->getServiceOptions('redisCluster')['hosts']);
    }

    public function testFromEnvRestUrlDefault(): void
    {
        $settings = Settings::fromEnv(['root' => '/app']);

        $this->assertSame('http://127.0.0.1:8080', $settings->get('rest_url'));
    }

    public function testFromEnvRestUrlOverride(): void
    {
        $settings = Settings::fromEnv([
            'root'           => '/app',
            'TALON_REST_URL' => 'http://api.test:9501',
        ]);

        $this->assertSame('http://api.test:9501', $settings->get('rest_url'));
    }

    public function testFromEnvUsesRootOverride(): void
    {
        $this->assertSame('/app', Settings::fromEnv(['root' => '/app'])->rootPath());
    }

    public function testGetDatabaseOptionsRejectsUnknownDriver(): void
    {
        $this->expectException(UnknownDriver::class);
        Settings::fromArray(['root' => '/app'])->getDatabaseOptions('oracle');
    }

    public function testGetReturnsExtraConfig(): void
    {
        $settings = Settings::fromArray(['root' => '/app', 'custom' => 'value']);

        $this->assertSame('value', $settings->get('custom'));
        $this->assertSame('fallback', $settings->get('missing', 'fallback'));
    }

    public function testGetServiceOptionsFromArray(): void
    {
        $settings = Settings::fromArray([
            'root'     => '/app',
            'services' => [
                'beanstalk' => ['host' => '127.0.0.1', 'port' => '11300'],
            ],
        ]);

        $this->assertSame(
            ['host' => '127.0.0.1', 'port' => '11300'],
            $settings->getServiceOptions('beanstalk')
        );
    }

    public function testGetServiceOptionsFromArrayForRedisCluster(): void
    {
        $settings = Settings::fromArray([
            'root'     => '/app',
            'services' => [
                'redisCluster' => [
                    'hosts' => ['10.0.0.1:6379', '10.0.0.2:6379'],
                    'auth'  => 'secret',
                ],
            ],
        ]);

        $this->assertSame(
            ['hosts' => ['10.0.0.1:6379', '10.0.0.2:6379'], 'auth' => 'secret'],
            $settings->getServiceOptions('redisCluster')
        );
    }

    public function testGetServiceOptionsUnknownNameReturnsEmptyArray(): void
    {
        $settings = Settings::fromArray(['root' => '/app']);

        $this->assertSame([], $settings->getServiceOptions('unknown'));
    }

    public function testMysqlDsnAndOptions(): void
    {
        $settings = Settings::fromArray(
            [
                'root' => '/app',
                'db'   => [
                    'mysql' => [
                        'host'     => '127.0.0.1',
                        'port'     => 3306,
                        'dbname'   => 'talon',
                        'username' => 'root',
                        'password' => 'secret',
                        'charset'  => 'utf8mb4',
                    ]
                ],
            ]
        );

        $this->assertSame(
            'mysql:host=127.0.0.1;port=3306;dbname=talon;charset=utf8mb4',
            $settings->getDatabaseDsn('mysql')
        );
        $this->assertSame('root', $settings->getDatabaseOptions('mysql')['username']);
    }

    public function testNonArrayPathsSectionFallsBackToDefaults(): void
    {
        $settings = Settings::fromArray(['root' => '/app', 'paths' => 'not-an-array']);

        $this->assertSame('/app/tests/_output', $settings->outputPath());
    }

    public function testNonArraySectionIsIgnored(): void
    {
        $settings = Settings::fromArray([
            'root'     => '/app',
            'services' => ['redis' => 'not-an-array'],
        ]);

        $this->assertSame([], $settings->getServiceOptions('redis'));
    }

    public function testPgsqlDsn(): void
    {
        $settings = Settings::fromArray(
            [
                'root' => '/app',
                'db'   => [
                    'pgsql' => [
                        'host' => '127.0.0.1',
                        'port' => 5432,
                        'dbname' => 'talon'
                    ]
                ],
            ]
        );

        $this->assertSame(
            'pgsql:host=127.0.0.1;port=5432;dbname=talon',
            $settings->getDatabaseDsn('pgsql')
        );
    }

    public function testPgsqlOptionsIncludeSchema(): void
    {
        $settings = Settings::fromArray(
            [
                'root' => '/app',
                'db'   => [
                    'pgsql' => [
                        'host'   => '127.0.0.1',
                        'port'   => 5432,
                        'dbname' => 'talon',
                        'schema' => 'public',
                    ]
                ],
            ]
        );

        $this->assertSame('public', $settings->getDatabaseOptions('pgsql')['schema']);
    }

    public function testRootPathTrimsSlashes(): void
    {
        $settings = Settings::fromArray(['root' => '/app/']);

        $this->assertSame('/app', $settings->rootPath());
        $this->assertSame('/app/sub', $settings->rootPath('/sub'));
    }

    public function testSqliteDsn(): void
    {
        $settings = Settings::fromArray([
            'root' => '/app',
            'db'   => ['sqlite' => ['dbname' => ':memory:']],
        ]);

        $this->assertSame('sqlite::memory:', $settings->getDatabaseDsn('sqlite'));
    }

    public function testUnknownDriverThrows(): void
    {
        $this->expectException(UnknownDriver::class);
        Settings::fromArray(['root' => '/app'])->getDatabaseDsn('oracle');
    }
}
