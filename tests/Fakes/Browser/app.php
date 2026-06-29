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
use Phalcon\Session\Adapter\Stream;
use Phalcon\Session\Manager;

// Keep native session start CLI-safe: no cookie/cache headers (avoids
// "headers already sent" warnings under failOnWarning). Only before the
// session is active - a fresh app is built per request, and ini_set() on
// session.* warns once a session has started.
if (PHP_SESSION_NONE === session_status()) {
    ini_set('session.use_cookies', '0');
    ini_set('session.use_only_cookies', '0');
    ini_set('session.cache_limiter', '');
}

$savePath = dirname(__DIR__, 1) . '/../_output/session';
if (!is_dir($savePath)) {
    mkdir($savePath, 0o775, true);
}

$di = new FactoryDefault();

$di->setShared('session', function () use ($savePath) {
    $manager = new Manager();
    $manager->setAdapter(new Stream(['savePath' => $savePath]));
    $manager->start();

    return $manager;
});

$di->setShared('dispatcher', function () {
    $dispatcher = new Dispatcher();
    $dispatcher->setDefaultNamespace('Phalcon\\Talon\\Tests\\Fakes\\Browser');

    return $dispatcher;
});

$application = new Application($di);
$application->useImplicitView(false);

return $application;
