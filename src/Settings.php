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

use function dirname;
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
    private const DRIVERS = ['mysql', 'pgsql', 'sqlite'];

    /**
     * @param array<string, array<string, mixed>> $db
     * @param array<string, mixed>                $redis
     * @param array<string, mixed>                $memcached
     * @param array<string, mixed>                $extra
     * @param array<string, mixed>                $paths
     */
    private function __construct(
        private string $root,
        private array $db,
        private array $redis,
        private array $memcached,
        private array $extra = [],
        private array $paths = [],
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
        unset($extra['root'], $extra['db'], $extra['redis'], $extra['memcached'], $extra['paths']);

        return new self(
            $root,
            self::sectionOfArrays($config, 'db'),
            self::section($config, 'redis'),
            self::section($config, 'memcached'),
            $extra,
            self::section($config, 'paths'),
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

        return new self(
            $root,
            [
                'mysql' => [
                    'host'     => $env('DATA_MYSQL_HOST', '127.0.0.1'),
                    'port'     => (int) $env('DATA_MYSQL_PORT', '3306'),
                    'dbname'   => $env('DATA_MYSQL_NAME', 'talon'),
                    'username' => $env('DATA_MYSQL_USER', 'root'),
                    'password' => $env('DATA_MYSQL_PASS'),
                    'charset'  => $env('DATA_MYSQL_CHARSET', 'utf8mb4'),
                ],
                'pgsql' => [
                    'host'     => $env('DATA_POSTGRES_HOST', '127.0.0.1'),
                    'port'     => (int) $env('DATA_POSTGRES_PORT', '5432'),
                    'dbname'   => $env('DATA_POSTGRES_NAME', 'talon'),
                    'username' => $env('DATA_POSTGRES_USER', 'postgres'),
                    'password' => $env('DATA_POSTGRES_PASS'),
                ],
                'sqlite' => [
                    'dbname' => $env('DATA_SQLITE_NAME', ':memory:'),
                ],
            ],
            [
                'host'  => $env('DATA_REDIS_HOST', '127.0.0.1'),
                'port'  => (int) $env('DATA_REDIS_PORT', '6379'),
                'index' => (int) $env('DATA_REDIS_NAME', '0'),
            ],
            [
                'host'   => $env('DATA_MEMCACHED_HOST', '127.0.0.1'),
                'port'   => (int) $env('DATA_MEMCACHED_PORT', '11211'),
                'weight' => (int) $env('DATA_MEMCACHED_WEIGHT', '0'),
            ],
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

        return $this->db[$driver] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getMemcachedOptions(): array
    {
        return $this->memcached;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRedisOptions(): array
    {
        return $this->redis;
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

        return $relative === '' ? $root : $root . '/' . ltrim($relative, '/');
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
