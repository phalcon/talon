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
use stdClass;

/**
 * An app whose getDI() returns something that is not a DiInterface at all,
 * driving Browser\Client::extractSetCookies()'s instanceof guard. If the
 * guard were skipped, has('cookies') would be called on stdClass and error.
 */
final class FakeAppWithNonDiContainer
{
    public function getDI(): stdClass
    {
        return new stdClass();
    }

    public function handle(string $uri): ResponseInterface
    {
        return (new Response())->setContent($uri);
    }
}
