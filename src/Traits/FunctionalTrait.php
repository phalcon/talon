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

namespace Phalcon\Talon\Traits;

use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Talon\Exceptions\InvalidApplication;
use Phalcon\Talon\Exceptions\MissingService;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;

use function get_debug_type;
use function is_object;
use function method_exists;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait FunctionalTrait
{
    protected bool $resetSuperglobals = false;

    private ?object $application = null;

    private mixed $response = null;

    public function dispatch(string $url): void
    {
        $factory = $this->appFactory();
        $app     = $factory();

        if (!is_object($app) || !method_exists($app, 'handle')) {
            throw new InvalidApplication(get_debug_type($app));
        }

        $this->application = $app;
        $this->response    = $app->handle($url);
    }

    public function getContent(): string
    {
        return $this->response()->getContent();
    }

    abstract protected function appFactory(): callable;

    protected function dispatcher(): Dispatcher
    {
        $dispatcher = $this->di()->getShared('dispatcher');
        if (!$dispatcher instanceof Dispatcher) {
            throw new MissingService('dispatcher');
        }

        return $dispatcher;
    }

    protected function resolveDi(InjectionAwareInterface $application): DiInterface
    {
        $di = $application->getDI();
        if (!$di instanceof DiInterface) {
            throw new ResponseNotDispatched();
        }

        return $di;
    }

    protected function response(): ResponseInterface
    {
        if (!$this->response instanceof ResponseInterface) {
            throw new ResponseNotDispatched();
        }

        return $this->response;
    }

    private function di(): DiInterface
    {
        if (!$this->application instanceof InjectionAwareInterface) {
            throw new ResponseNotDispatched();
        }

        return $this->resolveDi($this->application);
    }
}
