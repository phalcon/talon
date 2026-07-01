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

use Phalcon\Talon\Exceptions\MissingService;
use Phalcon\Talon\Tests\Fakes\App\FakeAppWithMissingDispatcher;
use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;

final class FunctionalMissingServiceTest extends TestCase
{
    use FunctionalTrait;
    use FunctionalAssertionsTrait;

    protected function appFactory(): callable
    {
        return static fn (): object => new FakeAppWithMissingDispatcher();
    }

    public function testMissingDispatcherThrows(): void
    {
        $this->dispatch('/');

        $this->expectException(MissingService::class);

        $this->assertController('test');
    }
}
