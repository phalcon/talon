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

use function uniqid;

final class ServicesTraitTest extends TestCase
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

    public function testRedisRoundTrip(): void
    {
        $this->setRedisKey('talon:test', 'value');

        $this->assertTrue($this->hasRedisKey('talon:test'));
        $this->assertSame('value', $this->getRedisKey('talon:test'));
    }

    public function testMemcachedRoundTrip(): void
    {
        $this->clearMemcached();
        $this->setMemcachedKey('talon_test', 'value');

        $this->assertTrue($this->hasMemcachedKey('talon_test'));
        $this->assertSame('value', $this->getMemcachedKey('talon_test'));
    }

    public function testHasMemcachedKeyWithStoredFalse(): void
    {
        $key = 'talon_false_' . uniqid();
        $this->setMemcachedKey($key, false);

        $this->assertTrue($this->hasMemcachedKey($key));
    }

    public function testDoesNotHaveKeys(): void
    {
        $this->assertTrue($this->doesNotHaveRedisKey('talon:absent:' . uniqid()));
        $this->assertTrue($this->doesNotHaveMemcachedKey('talon_absent_' . uniqid()));
    }

    public function testSendRedisCommand(): void
    {
        $this->sendRedisCommand('set', 'talon:cmd', 'sent');

        $this->assertSame('sent', $this->sendRedisCommand('get', 'talon:cmd'));
    }
}
