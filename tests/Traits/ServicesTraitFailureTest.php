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

namespace Phalcon\Talon\Tests\Traits;

use Memcached;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use Phalcon\Talon\Tests\Fakes\Services\FailingMemcachedClient;
use Phalcon\Talon\Tests\Fakes\Services\FailingRedisClient;
use Phalcon\Talon\Traits\ServicesTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;

/**
 * Drives the failure branches of ServicesTrait by overriding the client
 * seams with fakes whose operations fail.
 */
final class ServicesTraitFailureTest extends TestCase
{
    use ServicesTrait;

    protected function setUp(): void
    {
        Talon::useSettings(Settings::fromEnv());
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testClearMemcachedFailsWhenFlushFails(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->clearMemcached();
    }

    public function testSetMemcachedKeyFailsWhenSetFails(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->setMemcachedKey('talon_fail', 'value');
    }

    public function testSetRedisKeyFailsWhenSetFails(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->setRedisKey('talon:fail', 'value');
    }

    protected function createMemcachedClient(): Memcached
    {
        return new FailingMemcachedClient();
    }

    protected function createRedisClient(): RedisClient
    {
        return new FailingRedisClient();
    }
}
