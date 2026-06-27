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
use function is_int;
use function is_object;
use function method_exists;
use function str_contains;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait FunctionalTrait
{
    protected bool $resetSuperglobals = false;

    private ?object $application = null;

    private mixed $response = null;

    abstract protected function appFactory(): callable;

    public function assertAction(string $expected): void
    {
        $this->assertSame($expected, $this->dispatcher()->getActionName());
    }

    public function assertController(string $expected): void
    {
        $this->assertSame($expected, $this->dispatcher()->getControllerName());
    }

    public function assertDispatchIsForwarded(): void
    {
        $this->assertTrue($this->dispatcher()->wasForwarded());
    }

    /**
     * @param array<string, string> $expected
     */
    public function assertHeader(array $expected): void
    {
        $headers = $this->response()->getHeaders();

        foreach ($expected as $name => $value) {
            $this->assertSame($value, $headers->get($name));
        }
    }

    public function assertRedirectTo(string $location): void
    {
        $this->assertSame($location, $this->response()->getHeaders()->get('Location'));
    }

    public function assertResponseCode(int | string $expected): void
    {
        $expected = is_int($expected) ? (string) $expected : $expected;

        $this->assertStringContainsString(
            $expected,
            (string) $this->response()->getHeaders()->get('Status')
        );
    }

    public function assertResponseContentContains(string $needle): void
    {
        $this->assertTrue(str_contains($this->getContent(), $needle));
    }

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

    private function di(): DiInterface
    {
        if (!$this->application instanceof InjectionAwareInterface) {
            throw new ResponseNotDispatched();
        }

        return $this->application->getDI();
    }

    private function dispatcher(): Dispatcher
    {
        $dispatcher = $this->di()->getShared('dispatcher');
        if (!$dispatcher instanceof Dispatcher) {
            throw new MissingService('dispatcher');
        }

        return $dispatcher;
    }

    private function response(): ResponseInterface
    {
        if (!$this->response instanceof ResponseInterface) {
            throw new ResponseNotDispatched();
        }

        return $this->response;
    }
}
