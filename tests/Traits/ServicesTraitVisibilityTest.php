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
use Phalcon\Talon\Tests\Fakes\Services\ServicesTraitStubHost;
use Phalcon\Talon\Tests\Fakes\Services\StubMemcachedClient;
use Phalcon\Talon\Tests\Fakes\Services\StubRedisClient;
use PHPUnit\Framework\SkippedWithMessageException;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * Exercises the ServicesTrait public API from outside the composing class
 * and the protected seams through a subclass of a plain composing host, so
 * the declared visibility of every trait method is observable behavior.
 * The configured services point at an unreachable port on purpose: if the
 * trait ever ignores the overridden seams, it skips instead of touching
 * (or flushing) any live backend.
 */
final class ServicesTraitVisibilityTest extends TestCase
{
    private ServicesTraitStubHost $host;

    private StubMemcachedClient $memcachedClient;

    private StubRedisClient $redisClient;

    protected function setUp(): void
    {
        Talon::useSettings(
            Settings::fromArray([
                'root'     => dirname(__DIR__, 2),
                'services' => [
                    'memcached' => ['host' => '127.0.0.1', 'port' => 1],
                    'redis'     => ['host' => '127.0.0.1', 'port' => 1],
                ],
            ])
        );

        $this->memcachedClient = new StubMemcachedClient();
        $this->redisClient     = new StubRedisClient();
        $this->host            = new ServicesTraitStubHost(
            $this->memcachedClient,
            $this->redisClient
        );
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testClearMemcachedIsCallableExternally(): void
    {
        $this->callHost(fn () => $this->host->clearMemcached());

        $this->assertTrue($this->memcachedClient->flushed);
    }

    public function testDoesNotHaveMemcachedKeyIsCallableExternally(): void
    {
        $this->memcachedClient->resultCode = Memcached::RES_NOTFOUND;

        $this->assertTrue(
            $this->callHost(
                fn () => $this->host->doesNotHaveMemcachedKey('talon_absent')
            )
        );
    }

    public function testDoesNotHaveRedisKeyIsCallableExternally(): void
    {
        $this->redisClient->returns['exists'] = 0;

        $this->assertTrue(
            $this->callHost(
                fn () => $this->host->doesNotHaveRedisKey('talon:absent')
            )
        );
    }

    public function testGetMemcachedKeyUsesTheOverriddenClientSeam(): void
    {
        $this->memcachedClient->value = 'memcached-value';

        $this->assertSame(
            'memcached-value',
            $this->callHost(fn () => $this->host->getMemcachedKey('talon_get'))
        );
    }

    public function testGetRedisKeyUsesTheOverriddenClientSeam(): void
    {
        $this->redisClient->returns['get'] = 'redis-value';

        $this->assertSame(
            'redis-value',
            $this->callHost(fn () => $this->host->getRedisKey('talon:get'))
        );
    }

    public function testHasMemcachedKeyIsCallableExternally(): void
    {
        $this->memcachedClient->resultCode = Memcached::RES_SUCCESS;

        $this->assertTrue(
            $this->callHost(fn () => $this->host->hasMemcachedKey('talon_has'))
        );
    }

    public function testHasRedisKeyIsCallableExternally(): void
    {
        $this->redisClient->returns['exists'] = 1;

        $this->assertTrue(
            $this->callHost(fn () => $this->host->hasRedisKey('talon:has'))
        );
    }

    public function testMemcachedSkipsWhenAllStatsAreEmpty(): void
    {
        $this->memcachedClient->stats = ['127.0.0.1:11211' => []];

        $this->assertHostSkips(
            'Memcached is not reachable',
            fn () => $this->host->getMemcachedKey('talon_get')
        );
    }

    public function testMemcachedUnavailableUsesTheOverriddenSeam(): void
    {
        $this->host->hasMemcached = false;

        $this->assertHostSkips(
            'The memcached extension is not loaded',
            fn () => $this->host->getMemcachedKey('talon_get')
        );
    }

    public function testRedisUnavailableUsesTheOverriddenSeam(): void
    {
        $this->host->hasRedis = false;

        $this->assertHostSkips(
            'The predis/predis package is not installed',
            fn () => $this->host->getRedisKey('talon:get')
        );
    }

    public function testSendRedisCommandIsCallableExternally(): void
    {
        $this->redisClient->returns['ping'] = 'PONG';

        $this->assertSame(
            'PONG',
            $this->callHost(fn () => $this->host->sendRedisCommand('ping'))
        );
    }

    public function testSetMemcachedKeyDefaultsToNoExpiration(): void
    {
        $this->callHost(
            fn () => $this->host->setMemcachedKey('talon_set', 'value')
        );

        $this->assertSame(
            ['talon_set', 'value', 0],
            $this->memcachedClient->setArgs
        );
    }

    public function testSetRedisKeyIsCallableExternally(): void
    {
        $this->redisClient->returns['set'] = 'OK';

        $this->callHost(fn () => $this->host->setRedisKey('talon:set', 'value'));

        $this->assertSame(
            [['set', ['talon:set', 'value']]],
            $this->redisClient->calls
        );
    }

    private function assertHostSkips(string $needle, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the host call to be skipped');
        } catch (SkippedWithMessageException $exception) {
            $this->assertStringContainsString($needle, $exception->getMessage());
        }
    }

    private function callHost(callable $action): mixed
    {
        try {
            return $action();
        } catch (SkippedWithMessageException $exception) {
            $this->fail(
                'The host call was unexpectedly skipped: ' . $exception->getMessage()
            );
        }
    }
}
