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
 * Reports a reachable server and records flush() calls, so the
 * clearMemcached() success path is testable without flushing the live
 * backend (a global flush would race parallel mutation-testing processes).
 */
final class SpyMemcachedClient extends Memcached
{
    public bool $flushed = false;

    public function __construct(?string $persistentId = null)
    {
        parent::__construct($persistentId);
    }

    public function flush($delay = 0): bool
    {
        $this->flushed = true;

        return true;
    }

    public function getStats(?string $type = null): array | false
    {
        return ['127.0.0.1:11211' => ['pid' => 1]];
    }
}
