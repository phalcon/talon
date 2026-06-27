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

namespace Phalcon\Talon\Tests\Fixtures\App;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;

class TestController extends Controller
{
    public function emptyAction(): ResponseInterface
    {
        return $this->response->setContent('empty');
    }

    public function helloAction(): ResponseInterface
    {
        return $this->response->setContent('Hello Nikos');
    }
}
