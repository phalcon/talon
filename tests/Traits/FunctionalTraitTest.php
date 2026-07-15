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

namespace Phalcon\Talon\Tests\Traits;

use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithMissingDispatcher;
use Phalcon\Talon\Tests\Fakes\FunctionalFixture;
use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

final class FunctionalTraitTest extends TestCase
{
    use FunctionalAssertionsTrait;
    use FunctionalTrait;

    public function testAssertActionFailsOnMismatch(): void
    {
        $this->dispatch('/test/hello');

        $this->expectException(AssertionFailedError::class);

        $this->assertAction('other');
    }

    public function testAssertDispatchIsForwarded(): void
    {
        $this->dispatch('/test/forward');

        $this->assertDispatchIsForwarded();
        $this->assertAction('empty');
    }

    public function testAssertDispatchIsForwardedFailsWhenNotForwarded(): void
    {
        $this->dispatch('/test/hello');

        $this->expectException(AssertionFailedError::class);

        $this->assertDispatchIsForwarded();
    }

    public function testAssertHeader(): void
    {
        $this->dispatch('/test/header');

        $this->assertHeader(['X-Talon' => 'yes']);
    }

    public function testAssertionBeforeDispatchThrows(): void
    {
        $this->expectException(ResponseNotDispatched::class);

        $this->assertController('test');
    }

    public function testAssertRedirectTo(): void
    {
        $this->dispatch('/test/redirect');

        $this->assertRedirectTo('/target');
    }

    public function testAssertResponseCode(): void
    {
        $this->dispatch('/test/status');

        $this->assertResponseCode(404);
        $this->assertResponseCode('404');
    }

    public function testAssertResponseCodeFailsWhenStatusHeaderMissing(): void
    {
        $this->dispatch('/test/hello');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseCode(404);
    }

    public function testAssertResponseContentContainsFailsOnMissingNeedle(): void
    {
        $this->dispatch('/test/hello');

        $this->expectException(AssertionFailedError::class);

        $this->assertResponseContentContains('absent');
    }

    public function testDispatchAndAssertControllerAction(): void
    {
        $this->dispatch('/test/hello');

        $this->assertController('test');
        $this->assertAction('hello');
        $this->assertResponseContentContains('Operator');
        $this->assertStringContainsString('Operator', $this->getContent());
    }

    public function testGetContentBeforeDispatchThrows(): void
    {
        $this->expectException(ResponseNotDispatched::class);

        $this->getContent();
    }

    public function testProtectedHelpersAreAccessibleFromSubclass(): void
    {
        $child = new class () extends FunctionalFixture {
            public function callDispatcher(): Dispatcher
            {
                return $this->dispatcher();
            }

            public function callResolveDi(InjectionAwareInterface $application): DiInterface
            {
                return $this->resolveDi($application);
            }

            public function callResponse(): ResponseInterface
            {
                return $this->response();
            }
        };

        $child->dispatch('/test/hello');

        $this->assertInstanceOf(Dispatcher::class, $child->callDispatcher());
        $this->assertInstanceOf(ResponseInterface::class, $child->callResponse());
        $this->assertInstanceOf(
            DiInterface::class,
            $child->callResolveDi(new FakeAppWithMissingDispatcher())
        );
    }

    public function testPublicApiIsCallableFromOutside(): void
    {
        $fixture = new FunctionalFixture();

        $fixture->dispatch('/test/hello');
        $this->assertSame('Hello Operator', $fixture->getContent());
        $fixture->assertAction('hello');
        $fixture->assertController('test');
        $fixture->assertResponseContentContains('Operator');

        $fixture->dispatch('/test/forward');
        $fixture->assertDispatchIsForwarded();

        $fixture->dispatch('/test/header');
        $fixture->assertHeader(['X-Talon' => 'yes']);

        $fixture->dispatch('/test/redirect');
        $fixture->assertRedirectTo('/target');

        $fixture->dispatch('/test/status');
        $fixture->assertResponseCode(404);
    }

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../Fakes/App/app.php';
    }
}
