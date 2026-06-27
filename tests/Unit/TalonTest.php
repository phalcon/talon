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

namespace Phalcon\Talon\Tests\Unit;

use Phalcon\Talon\Contracts\Settings as SettingsContract;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use PHPUnit\Framework\TestCase;

final class TalonTest extends TestCase
{
    protected function tearDown(): void
    {
        Talon::reset();
        parent::tearDown();
    }

    public function testSettingsLazilyFallsBackToEnv(): void
    {
        Talon::reset();
        $this->assertInstanceOf(SettingsContract::class, Talon::settings());
    }

    public function testUseSettingsRegistersTheSlot(): void
    {
        $settings = Settings::fromArray(['root' => '/app']);
        Talon::useSettings($settings);

        $this->assertSame($settings, Talon::settings());
    }

    public function testBootReturnsAndRegistersSettings(): void
    {
        $settings = Settings::fromArray(['root' => '/app']);

        $this->assertSame($settings, Talon::boot($settings));
        $this->assertSame($settings, Talon::settings());
    }

    public function testResetClearsTheSlot(): void
    {
        $settings = Settings::fromArray(['root' => '/custom']);
        Talon::useSettings($settings);
        Talon::reset();

        $this->assertNotSame($settings, Talon::settings());
    }
}
