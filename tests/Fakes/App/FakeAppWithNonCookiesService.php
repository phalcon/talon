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
use Phalcon\Http\ResponseInterface;
use stdClass;

/**
 * A DI-aware app whose 'cookies' service resolves to something that isn't a
 * Phalcon\Http\Response\Cookies, driving Browser\Client::extractSetCookies()'s
 * instanceof Cookies guard.
 */
final class FakeAppWithNonCookiesService implements InjectionAwareInterface
{
    private DiInterface $di;

    public function __construct()
    {
        $this->di = new Di();
        $this->di->setShared('cookies', fn () => new stdClass());
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
