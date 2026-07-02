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

namespace Phalcon\Talon\Tests\Unit\Bootstrap;

use Phalcon\Config\Config;
use Phalcon\Di\DiInterface;
use Phalcon\Talon\Bootstrap\DiFactory;
use Phalcon\Talon\Settings;
use PHPUnit\Framework\TestCase;

final class DiFactoryTest extends TestCase
{
    public function testConfigServiceExposesTheRootPath(): void
    {
        $factory = new DiFactory(Settings::fromArray(['root' => '/app']));

        $di = $factory->create();

        $config = $di->getShared('config');
        $this->assertInstanceOf(Config::class, $config);
        $this->assertSame('/app', $config->get('root'));
    }

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
