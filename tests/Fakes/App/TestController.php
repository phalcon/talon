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

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;

class TestController extends Controller
{
    public function emptyAction(): ResponseInterface
    {
        return $this->response->setContent('empty');
    }

    public function forwardAction(): void
    {
        $this->dispatcher->forward(
            [
                'controller' => 'test',
                'action'     => 'empty',
            ]
        );
    }

    public function headerAction(): ResponseInterface
    {
        $this->response->setHeader('X-Talon', 'yes');

        return $this->response->setContent('header');
    }

    public function helloAction(): ResponseInterface
    {
        return $this->response->setContent('Hello Operator');
    }

    public function redirectAction(): ResponseInterface
    {
        $this->response->setStatusCode(302, 'Found');
        $this->response->setHeader('Location', '/target');

        return $this->response;
    }

    public function statusAction(): ResponseInterface
    {
        $this->response->setStatusCode(404, 'Not Found');

        return $this->response->setContent('nope');
    }
}
