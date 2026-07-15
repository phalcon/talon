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

use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use Phalcon\Talon\Traits\RestTrait;

/**
 * Covers the seams RestTraitTest overrides: the default (real) HTTP client and
 * the base URL resolved from Settings.
 */
final class RestTraitDefaultsTest extends AbstractUnitTestCase
{
    use RestTrait;

    protected function tearDown(): void
    {
        Talon::reset();

        parent::tearDown();
    }

    public function testDefaultHttpClientIsNull(): void
    {
        $this->assertNull($this->restHttpClient());
    }

    public function testRestBaseUrlComesFromSettings(): void
    {
        Talon::useSettings(Settings::fromArray([
            'root'     => '/app',
            'rest_url' => 'http://from.settings:9000',
        ]));

        $this->assertSame('http://from.settings:9000', $this->restBaseUrl());
    }

    public function testRestBaseUrlFallsBackWhenEmpty(): void
    {
        Talon::useSettings(Settings::fromArray([
            'root'     => '/app',
            'rest_url' => '',
        ]));

        $this->assertSame('http://127.0.0.1:8080', $this->restBaseUrl());
    }

    public function testRestBaseUrlFallsBackWhenNotAString(): void
    {
        Talon::useSettings(Settings::fromArray([
            'root'     => '/app',
            'rest_url' => 123,
        ]));

        $this->assertSame('http://127.0.0.1:8080', $this->restBaseUrl());
    }
}
