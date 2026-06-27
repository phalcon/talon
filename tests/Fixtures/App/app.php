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

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;

$di = new FactoryDefault();
$di->setShared('dispatcher', function () {
    $dispatcher = new Dispatcher();
    $dispatcher->setDefaultNamespace('Phalcon\\Talon\\Tests\\Fixtures\\App');

    return $dispatcher;
});

$application = new Application($di);
$application->useImplicitView(false);

return $application;
