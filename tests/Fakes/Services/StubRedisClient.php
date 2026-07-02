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
 * Fully configurable Redis double: connect() is a no-op and every command
 * is recorded and answered from the canned $returns map, so every
 * ServicesTrait redis path is observable without a live server.
 */
final class StubRedisClient extends Client
{
    /** @var array<int, array{string, array<int, mixed>}> */
    public array $calls = [];

    /** @var array<string, mixed> */
    public array $returns = [];

    public function __call($commandID, $arguments)
    {
        $this->calls[] = [$commandID, $arguments];

        return $this->returns[$commandID] ?? null;
    }

    public function connect()
    {
    }
}
