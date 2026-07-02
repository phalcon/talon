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

namespace Phalcon\Talon\Tests\Fakes\App;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\Response;
use Phalcon\Http\Response\Cookies;
use Phalcon\Http\ResponseInterface;

/**
 * A real Cookies service populated, through its own public set() API, with
 * one non-CookieInterface entry and one CookieInterface entry with a
 * non-scalar value - driving Browser\Client::extractSetCookies()'s two
 * innermost continue branches. handle() never touches Phalcon\Mvc\Application,
 * so its automatic cookie-sending never runs - if it did, PHP's native
 * setcookie() would fatal on the non-scalar value before extractSetCookies()
 * ever saw it.
 */
final class FakeAppWithMalformedCookies implements InjectionAwareInterface
{
    private DiInterface $di;

    public function __construct()
    {
        $this->di = new Di();
        $this->di->setShared('response', fn () => new Response());

        $cookies = new Cookies(false);
        $cookies->setDI($this->di);

        $this->di->set('Phalcon\Http\Cookie', fn () => new FakeNonCookieInterfaceCookie());
        $cookies->set('malformed', 'value', time() + 3600);

        $this->di->remove('Phalcon\Http\Cookie');
        $cookies->set('nonScalar', ['not', 'scalar'], time() + 3600);

        // Valid cookies AFTER the malformed ones: prove skipping continues the
        // loop, an int value is cast for rawurlencode(), a custom path is kept,
        // and a zero-expiration cookie stays a session cookie.
        $cookies->set('answer', 42, time() + 3600);
        $cookies->set('scoped', 'v', time() + 3600, '/sub');
        $cookies->set('sess', 'live');

        $this->di->setShared('cookies', fn () => $cookies);
    }

    public function getDI(): DiInterface
    {
        return $this->di;
    }

    public function handle(string $uri): ResponseInterface
    {
        return (new Response())->setContent($uri);
    }

    public function setDI(DiInterface $di): void
    {
        $this->di = $di;
    }
}
