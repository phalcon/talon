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

use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use Phalcon\Talon\Traits\ServicesTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function dirname;

/**
 * Exercises createMemcachedClient() directly, asserting the exact host and
 * port that reach Memcached::addServer() for configured, castable, missing
 * and non-scalar service options. No connection is made: getServerList()
 * reports the registered server without touching the network.
 */
final class ServicesTraitClientTest extends TestCase
{
    use ServicesTrait;

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testCreateMemcachedClientCastsScalarOptions(): void
    {
        $this->useMemcachedOptions(['host' => 19216811, 'port' => '11255']);

        /** @var array<int, array{host: string, port: int}> $servers */
        $servers = $this->createMemcachedClient()->getServerList();

        $this->assertCount(1, $servers);
        $this->assertSame('19216811', $servers[0]['host']);
        $this->assertSame(11255, $servers[0]['port']);
    }

    public function testCreateMemcachedClientDefaultsWhenOptionsAreMissing(): void
    {
        $this->useMemcachedOptions([]);

        /** @var array<int, array{host: string, port: int}> $servers */
        $servers = $this->createMemcachedClient()->getServerList();

        $this->assertCount(1, $servers);
        $this->assertSame('127.0.0.1', $servers[0]['host']);
        $this->assertSame(11211, $servers[0]['port']);
    }

    public function testCreateMemcachedClientDefaultsWhenOptionsAreNotScalar(): void
    {
        $this->useMemcachedOptions(['host' => ['192.0.2.10'], 'port' => [11255]]);

        /** @var array<int, array{host: string, port: int}> $servers */
        $servers = $this->createMemcachedClient()->getServerList();

        $this->assertCount(1, $servers);
        $this->assertSame('127.0.0.1', $servers[0]['host']);
        $this->assertSame(11211, $servers[0]['port']);
    }

    public function testCreateMemcachedClientUsesConfiguredHostAndPort(): void
    {
        $this->useMemcachedOptions(['host' => '192.0.2.10', 'port' => 11255]);

        /** @var array<int, array{host: string, port: int}> $servers */
        $servers = $this->createMemcachedClient()->getServerList();

        $this->assertCount(1, $servers);
        $this->assertSame('192.0.2.10', $servers[0]['host']);
        $this->assertSame(11255, $servers[0]['port']);
    }

    public function testSeamMethodVisibility(): void
    {
        // Execute the real seam bodies so this test covers the mutated
        // lines (infection pairs tests with mutants via line coverage);
        // the stub-host tests only ever run the overridden seams.
        $this->useMemcachedOptions([]);
        $this->createMemcachedClient();
        $this->createRedisClient();
        $this->assertTrue($this->memcachedAvailable());
        $this->assertTrue($this->redisAvailable());

        $this->assertTrue((new ReflectionMethod(self::class, 'createMemcachedClient'))->isProtected());
        $this->assertTrue((new ReflectionMethod(self::class, 'createRedisClient'))->isProtected());
        $this->assertTrue((new ReflectionMethod(self::class, 'memcachedAvailable'))->isProtected());
        $this->assertTrue((new ReflectionMethod(self::class, 'redisAvailable'))->isProtected());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function useMemcachedOptions(array $options): void
    {
        Talon::useSettings(
            Settings::fromArray([
                'root'     => dirname(__DIR__, 2),
                'services' => ['memcached' => $options],
            ])
        );
    }
}
