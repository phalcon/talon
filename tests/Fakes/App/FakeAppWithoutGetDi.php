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

use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;

/**
 * A valid handle()-only app with no getDI() method at all, driving
 * Browser\Client::extractSetCookies()'s method_exists() guard.
 */
final class FakeAppWithoutGetDi
{
    public function handle(string $uri): ResponseInterface
    {
        return (new Response())->setContent($uri);
    }
}
