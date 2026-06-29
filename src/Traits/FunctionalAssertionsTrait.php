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

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Dispatcher;

use function is_int;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait FunctionalAssertionsTrait
{
    abstract public function getContent(): string;

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
        $this->assertStringContainsString($needle, $this->getContent());
    }

    abstract protected function dispatcher(): Dispatcher;

    abstract protected function response(): ResponseInterface;
}
