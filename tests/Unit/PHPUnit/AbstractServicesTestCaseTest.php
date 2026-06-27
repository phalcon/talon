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

namespace Phalcon\Talon\Tests\Unit\PHPUnit;

use Phalcon\Talon\PHPUnit\AbstractServicesTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;

final class AbstractServicesTestCaseTest extends AbstractServicesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Talon::useSettings(Settings::fromEnv(['root' => '/app']));
    }

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testRedisRoundTrip(): void
    {
        $this->setRedisKey('talon:abs', 'ok');

        $this->assertSame('ok', $this->getRedisKey('talon:abs'));
    }
}
