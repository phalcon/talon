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

use ArrayObject;
use Phalcon\Talon\Bootstrap\Runner;
use Phalcon\Talon\Bootstrap\Stage;
use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;
use PHPUnit\Framework\TestCase;

final class RunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Talon::reset();
        parent::tearDown();
    }

    public function testRunsStagesInOrderWithHooks(): void
    {
        $order    = new ArrayObject();
        $settings = Settings::fromArray(['root' => '/app']);

        $runner = new class ($settings, $order) extends Runner {
            public function __construct(Settings $settings, private ArrayObject $order)
            {
                parent::__construct($settings);
            }

            protected function initEnvironment(): void
            {
                $this->order->append('env');
            }

            protected function initDirectories(): void
            {
                $this->order->append('dirs');
            }

            protected function initSettings(): void
            {
                $this->order->append('settings');
            }
        };

        $runner
            ->before(Stage::Environment, function () use ($order): void {
                $order->append('before-env');
            })
            ->after(Stage::Settings, function () use ($order): void {
                $order->append('after-settings');
            });

        $result = $runner->boot();

        $this->assertSame($settings, $result);
        $this->assertSame(
            ['before-env', 'env', 'dirs', 'settings', 'after-settings'],
            $order->getArrayCopy()
        );
    }

    public function testRealRunnerRegistersSettingsIntoTalon(): void
    {
        Talon::reset();
        $settings = Settings::fromArray(['root' => '/app']);

        Runner::for($settings)->boot();

        $this->assertSame($settings, Talon::settings());
    }
}
