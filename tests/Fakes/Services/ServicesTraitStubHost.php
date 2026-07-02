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

namespace Phalcon\Talon\Tests\Fakes\Services;

use Memcached;
use Predis\Client as RedisClient;

/**
 * Overrides every protected ServicesTrait seam of the plain host, proving
 * the seams are subclass-overridable (protected, not private) and routing
 * all service traffic to configurable stubs.
 */
final class ServicesTraitStubHost extends ServicesTraitHost
{
    public bool $hasMemcached = true;

    public bool $hasRedis = true;

    public function __construct(
        public StubMemcachedClient $memcachedClient,
        public StubRedisClient $redisClient,
    ) {
        parent::__construct();
    }

    protected function createMemcachedClient(): Memcached
    {
        return $this->memcachedClient;
    }

    protected function createRedisClient(): RedisClient
    {
        return $this->redisClient;
    }

    protected function memcachedAvailable(): bool
    {
        return $this->hasMemcached;
    }

    protected function redisAvailable(): bool
    {
        return $this->hasRedis;
    }
}
