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
        $key = 'talon:test:' . uniqid();
        $this->setRedisKey($key, 'value');

        $this->assertTrue($this->hasRedisKey($key));
        $this->assertSame('value', $this->getRedisKey($key));
    }

    public function testMemcachedRoundTrip(): void
    {
        $key = 'talon_test_' . uniqid();
        $this->setMemcachedKey($key, 'value');

        $this->assertTrue($this->hasMemcachedKey($key));
        $this->assertSame('value', $this->getMemcachedKey($key));
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
        $key = 'talon:cmd:' . uniqid();
        $this->sendRedisCommand('set', $key, 'sent');

        $this->assertSame('sent', $this->sendRedisCommand('get', $key));
    }
}
