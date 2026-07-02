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
use Phalcon\Talon\Tests\Fakes\Services\FakeMemcachedClient;
use Phalcon\Talon\Tests\Fakes\Services\FakeRedisClient;
use Phalcon\Talon\Traits\ServicesTrait;
use PHPUnit\Framework\SkippedWithMessageException;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;

/**
 * Drives the skip branches of ServicesTrait by overriding the client/availability
 * seams with fakes. Each test catches the skip exception so it passes (and records
 * coverage) instead of being reported as skipped.
 */
final class ServicesTraitSkipTest extends TestCase
{
    use ServicesTrait;

    private bool $hasMemcached = true;

    private bool $hasRedis = true;

    protected function setUp(): void
    {
        Talon::useSettings(Settings::fromEnv());
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testMemcachedSkipsWhenExtensionMissing(): void
    {
        $this->hasMemcached = false;

        $this->assertSkipped(
            'The memcached extension is not loaded',
            fn () => $this->getMemcachedKey('key')
        );
    }

    public function testMemcachedSkipsWhenUnreachable(): void
    {
        $this->assertSkipped(
            'Memcached is not reachable',
            fn () => $this->getMemcachedKey('key')
        );
    }

    public function testRedisSkipMessageContainsTheConnectionError(): void
    {
        try {
            $this->getRedisKey('key');
            $this->fail('Expected the test to be skipped');
        } catch (SkippedWithMessageException $exception) {
            $this->assertSame(
                'Redis is not reachable: Connection refused',
                $exception->getMessage()
            );
        }
    }

    public function testRedisSkipsWhenPackageMissing(): void
    {
        $this->hasRedis = false;

        $this->assertSkipped(
            'The predis/predis package is not installed',
            fn () => $this->getRedisKey('key')
        );
    }

    public function testRedisSkipsWhenUnreachable(): void
    {
        $this->assertSkipped(
            'Redis is not reachable',
            fn () => $this->getRedisKey('key')
        );
    }

    protected function createMemcachedClient(): Memcached
    {
        return new FakeMemcachedClient();
    }

    protected function createRedisClient(): RedisClient
    {
        return new FakeRedisClient();
    }

    protected function memcachedAvailable(): bool
    {
        return $this->hasMemcached;
    }

    protected function redisAvailable(): bool
    {
        return $this->hasRedis;
    }

    private function assertSkipped(string $needle, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the test to be skipped');
        } catch (SkippedWithMessageException $exception) {
            $this->assertStringContainsString($needle, $exception->getMessage());
        }
    }
}
