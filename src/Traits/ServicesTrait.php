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
        $options = $this->settings()->getMemcachedOptions();
        $host    = isset($options['host']) && is_scalar($options['host']) ? (string) $options['host'] : '127.0.0.1';
        $port    = isset($options['port']) && is_scalar($options['port']) ? (int) $options['port'] : 11211;

        $adapter = new Memcached();
        $adapter->addServer($host, $port);

        if (@$adapter->getVersion() === false) {
            $this->markTestSkipped('Memcached is not reachable');
        }

        return $adapter;
    }

    private function redis(): RedisClient
    {
        try {
            $client = new RedisClient($this->settings()->getRedisOptions());
            $client->connect();

            return $client;
        } catch (Exception $exception) {
            $this->markTestSkipped('Redis is not reachable: ' . $exception->getMessage());
        }
    }

    private function settings(): Settings
    {
        return Talon::settings();
    }
}
