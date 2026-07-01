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

use Phalcon\Di\DiInterface;

/**
 * Stands in for a real Phalcon\Http\Cookie without implementing
 * CookieInterface. Phalcon\Http\Response\Cookies::set() resolves cookie
 * instances through its container under the 'Phalcon\Http\Cookie' service
 * name and unconditionally calls setDI() on whatever comes back - overriding
 * that service with this class is how FakeAppWithMalformedCookies gets a
 * non-CookieInterface entry into Cookies::getCookies() through the real,
 * public API.
 */
final class FakeNonCookieInterfaceCookie
{
    public function setDI(DiInterface $container): void
    {
    }
}
