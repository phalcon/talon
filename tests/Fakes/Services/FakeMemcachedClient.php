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
 * Reports an unreachable server: getStats() returns false, driving the
 * "Memcached is not reachable" skip branch in ServicesTrait.
 */
final class FakeMemcachedClient extends Memcached
{
    public function getStats(?string $type = null): array | false
    {
        return false;
    }
}
