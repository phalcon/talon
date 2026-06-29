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

use Phalcon\Talon\Exceptions\ResponseNotDispatched;
use Phalcon\Talon\Traits\FunctionalAssertionsTrait;
use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;

final class FunctionalTraitTest extends TestCase
{
    use FunctionalTrait;
    use FunctionalAssertionsTrait;

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/../Fakes/App/app.php';
    }

    public function testDispatchAndAssertControllerAction(): void
    {
        $this->dispatch('/test/hello');

        $this->assertController('test');
        $this->assertAction('hello');
        $this->assertResponseContentContains('Operator');
        $this->assertStringContainsString('Operator', $this->getContent());
    }

    public function testAssertHeader(): void
    {
        $this->dispatch('/test/header');

        $this->assertHeader(['X-Talon' => 'yes']);
    }

    public function testAssertResponseCode(): void
    {
        $this->dispatch('/test/status');

        $this->assertResponseCode(404);
        $this->assertResponseCode('404');
    }

    public function testAssertRedirectTo(): void
    {
        $this->dispatch('/test/redirect');

        $this->assertRedirectTo('/target');
    }

    public function testAssertDispatchIsForwarded(): void
    {
        $this->dispatch('/test/forward');

        $this->assertDispatchIsForwarded();
        $this->assertAction('empty');
    }

    public function testAssertionBeforeDispatchThrows(): void
    {
        $this->expectException(ResponseNotDispatched::class);

        $this->assertController('test');
    }

    public function testGetContentBeforeDispatchThrows(): void
    {
        $this->expectException(ResponseNotDispatched::class);

        $this->getContent();
    }
}
