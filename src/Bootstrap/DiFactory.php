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
