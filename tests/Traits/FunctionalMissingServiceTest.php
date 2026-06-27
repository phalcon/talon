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

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\Response;
use Phalcon\Http\ResponseInterface;
use Phalcon\Talon\Exceptions\MissingService;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;
use stdClass;

final class FunctionalMissingServiceTest extends TestCase
{
    use FunctionalTrait;

    protected function appFactory(): callable
    {
        return static function (): object {
            return new class () implements InjectionAwareInterface {
                private DiInterface $di;

                public function __construct()
                {
                    $this->di = new Di();
                    $this->di->set('dispatcher', fn () => new stdClass());
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
            };
        };
    }

    public function testMissingDispatcherThrows(): void
    {
        $this->dispatch('/');

        $this->expectException(MissingService::class);

        $this->assertController('test');
    }
}
