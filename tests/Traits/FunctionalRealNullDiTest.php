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
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use Phalcon\Talon\Environment;
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;

/**
 * Covers FunctionalTrait::resolveDi()'s real null-DI throw, as opposed to
 * FunctionalNullDiTest (which fakes the branch via an overridden resolveDi()).
 * Only runs under the phalcon/phalcon (v6) provider - ext-phalcon's (v5)
 * InjectionAwareInterface::getDI() isn't nullable, so a class implementing
 * it with a nullable getDI() can't even be declared there.
 */
final class FunctionalRealNullDiTest extends TestCase
{
    use FunctionalTrait;
    use FunctionalAssertionsTrait;

    protected function appFactory(): callable
    {
        return static function (): object {
            return new class () implements InjectionAwareInterface {
                public function getDI(): ?DiInterface
                {
                    return null;
                }

                public function handle(string $uri): ResponseInterface
                {
                    return (new Response())->setContent($uri);
                }

                public function setDI(DiInterface $di): void
                {
                }
            };
        };
    }

    public function testRealResolveDiThrowsWhenGetDiReturnsNull(): void
    {
        if (!Environment::viaImplementation()) {
            $this->markTestSkipped(
                'InjectionAwareInterface::getDI() is only nullable under the phalcon/phalcon (v6) provider'
            );
        }

        $this->dispatch('/test/hello');

        $this->expectException(ResponseNotDispatched::class);

        $this->assertController('test');
    }
}
