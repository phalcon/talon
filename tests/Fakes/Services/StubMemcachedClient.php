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

/**
 * Fully configurable in-memory Memcached double: records flush() and set()
 * calls and returns canned values for get(), getResultCode() and getStats(),
 * so every ServicesTrait memcached path is observable without a live server.
 */
final class StubMemcachedClient extends Memcached
{
    public bool $flushed = false;

    public int $resultCode = Memcached::RES_SUCCESS;

    /** @var array<int, mixed> */
    public array $setArgs = [];

    /** @var array<string, array<string, mixed>>|false */
    public array | false $stats = ['127.0.0.1:11211' => ['pid' => 1]];

    public mixed $value = false;

    public function __construct(?string $persistentId = null)
    {
        parent::__construct($persistentId);
    }

    public function flush($delay = 0): bool
    {
        $this->flushed = true;

        return true;
    }

    public function get($key, $cache_cb = null, $get_flags = 0): mixed
    {
        return $this->value;
    }

    public function getResultCode(): int
    {
        return $this->resultCode;
    }

    public function getStats(?string $type = null): array | false
    {
        return $this->stats;
    }

    public function set($key, $value, $expiration = 0): bool
    {
        $this->setArgs = [$key, $value, $expiration];

        return true;
    }
}
