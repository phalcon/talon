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

use Predis\Client;

/**
 * Simulates a reachable Redis whose SET command fails: connect() succeeds
 * and set() returns no OK status, driving the failure assertions in
 * ServicesTrait.
 */
final class FailingRedisClient extends Client
{
    public function connect()
    {
    }

    public function set(mixed ...$arguments): mixed
    {
        return null;
    }
}
