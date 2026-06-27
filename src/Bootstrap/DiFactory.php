<?php

declare(strict_types=1);

namespace Phalcon\Talon\Bootstrap;

use Phalcon\Config\Config;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Talon\Contracts\Settings;

final class DiFactory
{
    public function __construct(private Settings $settings)
    {
    }

    public function create(?callable $register = null): DiInterface
    {
        $settings = $this->settings;

        $di = new FactoryDefault();
        $di->setShared('config', fn () => new Config([
            'root' => $settings->path(),
        ]));

        if ($register !== null) {
            $register($di);
        }

        return $di;
    }
}
