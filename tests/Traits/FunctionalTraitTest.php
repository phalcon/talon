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

use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;

final class FunctionalTraitTest extends TestCase
{
    use FunctionalTrait;

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../Fixtures/App/app.php';
    }

    public function testDispatchAndAssertControllerAction(): void
    {
        $this->dispatch('/test/hello');

        $this->assertController('test');
        $this->assertAction('hello');
        $this->assertResponseContentContains('Nikos');
    }
}
