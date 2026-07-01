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

use function extension_loaded;
use function ini_get;
use function mb_internal_encoding;
use function rmdir;
use function setlocale;
use function uniqid;

use const LC_ALL;

final class RunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Talon::reset();
        parent::tearDown();
    }

    public function testRunsStagesInOrderWithHooks(): void
    {
        /** @var ArrayObject<int, string> $order */
        $order    = new ArrayObject();
        $settings = Settings::fromArray(['root' => '/app']);

        $runner = new class ($settings, $order) extends Runner {
            /**
             * @param ArrayObject<int, string> $order
             */
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
        $settings = Settings::fromArray(['root' => dirname(__DIR__, 3)]);

        Runner::for($settings)->boot();

        $this->assertSame($settings, Talon::settings());
    }

    public function testBootCreatesTheOutputDirectory(): void
    {
        Talon::reset();
        $root = dirname(__DIR__, 2) . '/_output/runner-' . uniqid();
        $settings = Settings::fromArray(['root' => $root]);

        Runner::for($settings)->boot();

        $this->assertDirectoryExists($root . '/tests/_output');

        rmdir($root . '/tests/_output');
        rmdir($root . '/tests');
        rmdir($root);
    }

    public function testInitEnvironmentSetsIniLocaleAndMbstringDefaults(): void
    {
        Talon::reset();
        $settings = Settings::fromArray(['root' => '/app']);

        Runner::for($settings)->boot();

        $this->assertSame('1', ini_get('display_errors'));
        $this->assertSame('1', ini_get('display_startup_errors'));
        $this->assertSame('en_US.utf-8', setlocale(LC_ALL, '0'));
        $this->assertSame('UTF-8', mb_internal_encoding());

        if (extension_loaded('xdebug')) {
            $this->assertSame('1', ini_get('xdebug.cli_color'));
            $this->assertSame('On', ini_get('xdebug.dump_globals'));
            $this->assertSame('On', ini_get('xdebug.show_local_vars'));
            $this->assertSame('100', ini_get('xdebug.max_nesting_level'));
            $this->assertSame('4', ini_get('xdebug.var_display_max_depth'));
        }
    }
}
