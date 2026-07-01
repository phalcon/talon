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

namespace Phalcon\Talon;

use Phalcon\Talon\Contracts\Settings as SettingsContract;
use Phalcon\Talon\Exceptions\InvalidConfiguration;
use Phalcon\Talon\Exceptions\UnknownDriver;

use function array_filter;
use function dirname;
use function explode;
use function file_exists;
use function getcwd;
use function getenv;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function ltrim;
use function rtrim;
use function sprintf;

final class Settings implements SettingsContract
{
    private const DEFAULT_HOST = '127.0.0.1';
    private const DRIVERS      = ['mysql', 'pgsql', 'sqlite'];

    /**
     * @param array<string, mixed>          $extra
     * @param array<string, mixed>          $paths
     * @param array<string, ServiceOptions> $services
     */
    private function __construct(
        private string $root,
        private array $extra = [],
        private array $paths = [],
        private array $services = [],
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $root = $config['root'] ?? null;
        if (!is_string($root) || $root === '') {
            throw new InvalidConfiguration("the 'root' key is required and must be a non-empty string");
        }

        $extra = $config;
        unset(
            $extra['root'],
            $extra['db'],
            $extra['paths'],
            $extra['services']
        );

        $services = [];
        foreach (self::sectionOfArrays($config, 'db') as $name => $options) {
            $services[$name] = new ServiceOptions($name, $options);
        }

        foreach (self::section($config, 'services') as $name => $value) {
            $name = (string) $name;
            if ($value instanceof ServiceOptions) {
                $services[$name] = $value;
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $services[$name] = new ServiceOptions($name, $value);
            }
        }

        return new self(
            $root,
            $extra,
            self::section($config, 'paths'),
            $services,
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function fromEnv(array $overrides = []): self
    {
        $env = static function (string $key, string $default = '') use ($overrides): string {
            $value = $overrides[$key] ?? (getenv($key) !== false ? getenv($key) : ($_ENV[$key] ?? $default));

            return is_scalar($value) ? (string) $value : $default;
        };

        $rootOverride = $overrides['root'] ?? null;
        $root         = is_string($rootOverride) && $rootOverride !== '' ? $rootOverride : self::discoverRoot();

        // Simple `host`/`port`-shaped services (including each db driver)
        // read entirely from env vars, declared here rather than as one
        // hand-written method/array-literal each - add a new entry to grow
        // this list. Each field is [envKey, default, cast] - cast is 'int'
        // or null (string as-is).
        $serviceFields = [
            'mysql' => [
                'host'     => ['DATA_MYSQL_HOST', self::DEFAULT_HOST, null],
                'port'     => ['DATA_MYSQL_PORT', '3306', 'int'],
                'dbname'   => ['DATA_MYSQL_NAME', 'talon', null],
                'username' => ['DATA_MYSQL_USER', 'root', null],
                'password' => ['DATA_MYSQL_PASS', '', null],
                'charset'  => ['DATA_MYSQL_CHARSET', 'utf8mb4', null],
            ],
            'pgsql' => [
                'host'     => ['DATA_POSTGRES_HOST', self::DEFAULT_HOST, null],
                'port'     => ['DATA_POSTGRES_PORT', '5432', 'int'],
                'dbname'   => ['DATA_POSTGRES_NAME', 'talon', null],
                'username' => ['DATA_POSTGRES_USER', 'postgres', null],
                'password' => ['DATA_POSTGRES_PASS', '', null],
                'schema'   => ['DATA_POSTGRES_SCHEMA', '', null],
            ],
            'sqlite' => [
                'dbname' => ['DATA_SQLITE_NAME', ':memory:', null],
            ],
            'redis' => [
                'host'  => ['DATA_REDIS_HOST', self::DEFAULT_HOST, null],
                'port'  => ['DATA_REDIS_PORT', '6379', 'int'],
                'index' => ['DATA_REDIS_NAME', '0', 'int'],
            ],
            'memcached' => [
                'host'   => ['DATA_MEMCACHED_HOST', self::DEFAULT_HOST, null],
                'port'   => ['DATA_MEMCACHED_PORT', '11211', 'int'],
                'weight' => ['DATA_MEMCACHED_WEIGHT', '0', 'int'],
            ],
            'beanstalk' => [
                'host' => ['DATA_BEANSTALKD_HOST', '', null],
                'port' => ['DATA_BEANSTALKD_PORT', '', null],
            ],
        ];

        $services = [];
        foreach ($serviceFields as $name => $fields) {
            $options = [];
            foreach ($fields as $field => $spec) {
                [$envKey, $default, $cast] = $spec;
                $value           = $env($envKey, $default);
                $options[$field] = $cast === 'int' ? (int) $value : $value;
            }
            $services[$name] = new ServiceOptions($name, $options);
        }

        // redisCluster's `hosts` is a comma-separated env var, not a single
        // value - doesn't fit the field => envKey table shape above.
        $services['redisCluster'] = new ServiceOptions('redisCluster', [
            'hosts' => array_filter(explode(',', $env('DATA_REDIS_CLUSTER_HOSTS'))),
            'auth'  => $env('DATA_REDIS_CLUSTER_AUTH'),
        ]);

        return new self(
            $root,
            [
                'dump_file'       => $env('dump_file'),
                'initial_queries' => $env('initial_queries'),
            ],
            [],
            $services,
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }

    public function getDatabaseDsn(string $driver): string
    {
        $options = $this->getDatabaseOptions($driver);

        return match ($driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->optString($options, 'host'),
                $this->optString($options, 'port'),
                $this->optString($options, 'dbname'),
                $this->optString($options, 'charset', 'utf8mb4'),
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $this->optString($options, 'host'),
                $this->optString($options, 'port'),
                $this->optString($options, 'dbname'),
            ),
            'sqlite' => sprintf('sqlite:%s', $this->optString($options, 'dbname', ':memory:')),
            default  => throw new UnknownDriver($driver),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getDatabaseOptions(string $driver): array
    {
        if (!in_array($driver, self::DRIVERS, true)) {
            throw new UnknownDriver($driver);
        }

        return $this->getServiceOptions($driver);
    }

    /**
     * @return array<string, mixed>
     */
    public function getServiceOptions(string $name): array
    {
        return ($this->services[$name] ?? null)?->getOptions() ?? [];
    }

    public function cachePath(string $relative = ''): string
    {
        return $this->dir('cache', 'tests/_output/cache', $relative);
    }

    public function dataPath(string $relative = ''): string
    {
        return $this->dir('data', 'tests/_data', $relative);
    }

    public function logsPath(string $relative = ''): string
    {
        return $this->dir('logs', 'tests/_output/logs', $relative);
    }

    public function outputPath(string $relative = ''): string
    {
        return $this->dir('output', 'tests/_output', $relative);
    }

    public function rootPath(string $relative = ''): string
    {
        $root = rtrim($this->root, '/');

        if ($relative === '') {
            return $root === '' ? '/' : $root;
        }

        return $root . '/' . ltrim($relative, '/');
    }

    public function supportPath(string $relative = ''): string
    {
        return $this->dir('support', 'tests/support', $relative);
    }

    public function testsPath(string $relative = ''): string
    {
        return $this->dir('tests', 'tests', $relative);
    }

    private function dir(string $key, string $default, string $relative): string
    {
        $override = $this->paths[$key] ?? null;
        $sub      = is_string($override) ? $override : $default;

        if ($relative !== '') {
            $sub = rtrim($sub, '/') . '/' . ltrim($relative, '/');
        }

        return $this->rootPath($sub);
    }

    private static function discoverRoot(): string
    {
        $start   = getcwd() ?: '.';
        $current = $start;

        while (true) {
            if (file_exists($current . '/composer.json')) {
                return $current;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                return $start;
            }

            $current = $parent;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function optString(array $options, string $key, string $default = ''): string
    {
        $value = $options[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private static function section(array $config, string $key): array
    {
        $value = $config[$key] ?? [];
        if (!is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, array<string, mixed>>
     */
    private static function sectionOfArrays(array $config, string $key): array
    {
        $result = [];
        foreach (self::section($config, $key) as $name => $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $result[(string) $name] = $value;
            }
        }

        return $result;
    }
}
