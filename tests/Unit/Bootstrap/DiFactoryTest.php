<?php

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Unit\Bootstrap;

use Phalcon\Di\DiInterface;
use Phalcon\Talon\Bootstrap\DiFactory;
use Phalcon\Talon\Settings;
use PHPUnit\Framework\TestCase;

final class DiFactoryTest extends TestCase
{
    public function testCreatesDiWithConfig(): void
    {
        $factory = new DiFactory(Settings::fromArray(['root' => '/app']));

        $di = $factory->create();

        $this->assertInstanceOf(DiInterface::class, $di);
        $this->assertTrue($di->has('config'));
    }

    public function testRegisterCallbackRuns(): void
    {
        $factory = new DiFactory(Settings::fromArray(['root' => '/app']));

        $di = $factory->create(function (DiInterface $di): void {
            $di->set('marker', fn () => 'present');
        });

        $this->assertSame('present', $di->get('marker'));
    }
}
