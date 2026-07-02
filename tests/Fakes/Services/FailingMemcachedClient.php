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
 * Reports a reachable server whose write operations fail: set() and flush()
 * return false, driving the failure assertions in ServicesTrait.
 */
final class FailingMemcachedClient extends Memcached
{
    public function __construct(?string $persistentId = null)
    {
        parent::__construct($persistentId);
    }

    public function flush($delay = 0): bool
    {
        return false;
    }

    public function getStats(?string $type = null): array | false
    {
        return ['127.0.0.1:11211' => ['pid' => 1]];
    }

    public function set($key, $value, $expiration = 0): bool
    {
        return false;
    }
}
