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
use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;

final class FunctionalNullDiTest extends TestCase
{
    use FunctionalTrait;
    use FunctionalAssertionsTrait;

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../Fakes/App/app.php';
    }

    protected function resolveDi(InjectionAwareInterface $application): DiInterface
    {
        throw new ResponseNotDispatched();
    }

    public function testNullDiThrows(): void
    {
        $this->dispatch('/test/hello');

        $this->expectException(ResponseNotDispatched::class);

        $this->assertController('test');
    }
}
