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

namespace Phalcon\Talon\Tests\Fakes\Browser;

use Phalcon\Talon\Traits\FunctionalTrait;
use PHPUnit\Framework\TestCase;

final class FixtureSmokeTest extends TestCase
{
    use FunctionalTrait;

    protected function appFactory(): callable
    {
        return static fn () => require __DIR__ . '/app.php';
    }

    public function testFormRendersWithCsrfField(): void
    {
        $this->dispatch('/browser/form');

        $this->assertStringContainsString('<form method="post" action="/browser/submit">', $this->getContent());
        $this->assertStringContainsString('type="hidden"', $this->getContent());
        $this->assertStringContainsString('Log In', $this->getContent());
    }

    public function testSecuredShowsGuestWithoutSession(): void
    {
        $this->dispatch('/browser/secured');

        $this->assertStringContainsString('Guest', $this->getContent());
    }
}
