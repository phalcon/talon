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
 * An app that holds a reference to itself, as a real application does through
 * its container: the container keeps the services, and the services keep the
 * container. Reference counting alone can never free such a graph, so only the
 * cycle collector can release it.
 */
final class FakeAppWithSelfReference
{
    /**
     * @var array<int, self>
     */
    private array $cycle = [];

    public function __construct()
    {
        $this->cycle[] = $this;
    }

    public function handle(string $uri): ResponseInterface
    {
        return (new Response())->setContent($uri);
    }
}
