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
use Phalcon\Talon\Tests\Fakes\Services\SpyMemcachedClient;
use Phalcon\Talon\Traits\ServicesTrait;
use PHPUnit\Framework\TestCase;

/**
 * Drives the clearMemcached() success path through a recording spy instead
 * of the live backend, keeping the suite safe for parallel mutation runs.
 */
final class ServicesTraitClearTest extends TestCase
{
    use ServicesTrait;

    private SpyMemcachedClient $spy;

    protected function setUp(): void
    {
        Talon::useSettings(Settings::fromEnv());
        $this->spy = new SpyMemcachedClient();
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testClearMemcachedFlushesTheBackend(): void
    {
        $this->clearMemcached();

        $this->assertTrue($this->spy->flushed);
    }

    protected function createMemcachedClient(): Memcached
    {
        return $this->spy;
    }
}
