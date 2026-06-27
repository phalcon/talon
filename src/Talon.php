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

namespace Phalcon\Talon;

use Phalcon\Talon\Bootstrap\Runner;
use Phalcon\Talon\Contracts\Settings as SettingsContract;

final class Talon
{
    private static ?SettingsContract $settings = null;

    public static function boot(?SettingsContract $settings = null): SettingsContract
    {
        return Runner::for($settings ?? Settings::fromEnv())->boot();
    }

    public static function reset(): void
    {
        self::$settings = null;
    }

    public static function settings(): SettingsContract
    {
        return self::$settings ??= Settings::fromEnv();
    }

    public static function useSettings(SettingsContract $settings): void
    {
        self::$settings = $settings;
    }
}
