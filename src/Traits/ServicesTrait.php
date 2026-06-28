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

namespace Phalcon\Talon\Traits;

use Exception;
use Memcached;
use Phalcon\Talon\Contracts\Settings;
use Phalcon\Talon\Talon;
use Predis\Client as RedisClient;

use function class_exists;
use function extension_loaded;
use function is_scalar;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait ServicesTrait
{
    public function clearMemcached(): void
    {
        $this->memcached()->flush();
    }

    public function doesNotHaveMemcachedKey(string $key): bool
    {
        return !$this->hasMemcachedKey($key);
    }

    public function doesNotHaveRedisKey(string $key): bool
    {
        return !$this->hasRedisKey($key);
    }

    public function getMemcachedKey(string $key): mixed
    {
        return $this->memcached()->get($key);
    }

    public function getRedisKey(string $key): mixed
    {
        return $this->redis()->get($key);
    }

    public function hasMemcachedKey(string $key): bool
    {
        return $this->memcached()->get($key) !== false;
    }

    public function hasRedisKey(string $key): bool
    {
        return (bool) $this->redis()->exists($key);
    }

    public function sendRedisCommand(string $command, mixed ...$args): mixed
    {
        return $this->redis()->$command(...$args);
    }

    public function setMemcachedKey(string $key, mixed $value, int $expiration = 0): void
    {
        $this->memcached()->set($key, $value, $expiration);
    }

    public function setRedisKey(string $key, mixed $value): void
    {
        $this->redis()->set($key, $value);
    }

    private function memcached(): Memcached
    {
        if (!extension_loaded('memcached')) {
            // @codeCoverageIgnoreStart
            // Reached only when ext-memcached is absent; talon's CI loads it.
            $this->markTestSkipped('The memcached extension is not loaded');
            // @codeCoverageIgnoreEnd
        }

        $options = $this->settings()->getMemcachedOptions();
        $host    = isset($options['host']) && is_scalar($options['host']) ? (string) $options['host'] : '127.0.0.1';
        $port    = isset($options['port']) && is_scalar($options['port']) ? (int) $options['port'] : 11211;

        $adapter = new Memcached();
        $adapter->addServer($host, $port);

        if (@$adapter->getVersion() === false) {
            // @codeCoverageIgnoreStart
            // Only reached when Memcached is unreachable; the suite needs it up.
            $this->markTestSkipped('Memcached is not reachable');
            // @codeCoverageIgnoreEnd
        }

        return $adapter;
    }

    private function redis(): RedisClient
    {
        if (!class_exists(RedisClient::class)) {
            // @codeCoverageIgnoreStart
            // Reached only when predis/predis is not installed; talon's CI installs it.
            $this->markTestSkipped('The predis/predis package is not installed');
            // @codeCoverageIgnoreEnd
        }

        try {
            $client = new RedisClient($this->settings()->getRedisOptions());
            $client->connect();

            return $client;
            // @codeCoverageIgnoreStart
            // Only reached when Redis is unreachable; the suite needs it up.
        } catch (Exception $exception) {
            $this->markTestSkipped('Redis is not reachable: ' . $exception->getMessage());
        }
        // @codeCoverageIgnoreEnd
    }

    private function settings(): Settings
    {
        return Talon::settings();
    }
}
