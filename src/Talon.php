<?php

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
