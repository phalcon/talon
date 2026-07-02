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
use Phalcon\Talon\Tests\Fakes\Bootstrap\RecordingRunner;
use Phalcon\Talon\Tests\Fakes\Bootstrap\XdebugForcedRunner;
use PHPUnit\Framework\TestCase;

use function date_default_timezone_get;
use function date_default_timezone_set;
use function error_reporting;
use function extension_loaded;
use function file_put_contents;
use function fileperms;
use function filesize;
use function ini_get;
use function ini_set;
use function mb_internal_encoding;
use function rmdir;
use function setlocale;
use function umask;
use function uniqid;
use function unlink;

use const E_ALL;
use const E_ERROR;
use const FILE_APPEND;
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

        $runner = new RecordingRunner($settings, $order);

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
        // build/ is gitignored and outside the analyzers' scan tree, so
        // mutation-run debris (crashed mutants skip cleanup, sometimes with
        // mutated unreadable permissions) cannot break phpstan/phpcs.
        $root = dirname(__DIR__, 3) . '/build/runner-' . uniqid();
        $settings = Settings::fromArray(['root' => $root]);

        Runner::for($settings)->boot();

        $this->assertDirectoryExists($root . '/tests/_output');

        rmdir($root . '/tests/_output');
        rmdir($root . '/tests');
        rmdir($root);
    }

    public function testInitDirectoriesAppliesTheConfiguredPermissions(): void
    {
        // See testBootCreatesTheOutputDirectory for why build/ hosts this.
        $root     = dirname(__DIR__, 3) . '/build/' . uniqid('runner-', true);
        $settings = Settings::fromArray(['root' => $root]);

        $runner = new class ($settings) extends Runner {
            public function createDirectories(): void
            {
                $this->initDirectories();
            }
        };

        $previousUmask = umask(0);

        try {
            $runner->createDirectories();

            $output = $root . '/tests/_output';
            $this->assertDirectoryExists($output);

            $permissions = fileperms($output);
            $this->assertNotFalse($permissions);
            $this->assertSame(0o777, $permissions & 0o777);
        } finally {
            umask($previousUmask);
            rmdir($root . '/tests/_output');
            rmdir($root . '/tests');
            rmdir($root);
        }
    }

    public function testInitEnvironmentReconfiguresThePerturbedRuntime(): void
    {
        Talon::reset();
        $settings = Settings::fromArray(['root' => dirname(__DIR__, 3)]);

        // Spy on the protected extension seam: kills the protected->private
        // mutant (the parent's private copy would win, leaving the flag false)
        // while parent::isExtensionLoaded() keeps the real body covered.
        // initEnvironment() is exposed directly because PHP's stat cache
        // holds a single entry: boot()'s own directory checks would evict
        // the stale entry primed below before clearstatcache() runs.
        $runner = new class ($settings) extends Runner {
            public bool $extensionChecked = false;

            public function applyEnvironment(): void
            {
                $this->initEnvironment();
            }

            protected function isExtensionLoaded(string $extension): bool
            {
                $this->extensionChecked = true;

                return parent::isExtensionLoaded($extension);
            }
        };

        $statFile = dirname(__DIR__, 2) . '/_output/' . uniqid('statcache-', true) . '.txt';

        $originalErrorReporting = error_reporting();
        $originalTimezone       = date_default_timezone_get();
        $originalDisplayErrors  = (string) ini_get('display_errors');
        $originalStartupErrors  = (string) ini_get('display_startup_errors');
        $originalEncoding       = mb_internal_encoding();

        try {
            error_reporting(E_ERROR);
            date_default_timezone_set('Europe/Athens');
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            mb_internal_encoding('ISO-8859-1');

            // Prime the stat cache with a stale size; only clearstatcache()
            // inside initEnvironment() makes filesize() report 5 afterwards.
            file_put_contents($statFile, 'a');
            $this->assertSame(1, filesize($statFile));
            file_put_contents($statFile, 'bcde', FILE_APPEND);

            $runner->applyEnvironment();

            $this->assertSame(E_ALL, error_reporting());
            $this->assertSame('UTC', date_default_timezone_get());
            $this->assertSame('1', ini_get('display_errors'));
            $this->assertSame('1', ini_get('display_startup_errors'));
            $this->assertSame('UTF-8', mb_internal_encoding());
            $this->assertSame(5, filesize($statFile));
            $this->assertTrue($runner->extensionChecked);
        } finally {
            error_reporting($originalErrorReporting);
            date_default_timezone_set($originalTimezone);
            ini_set('display_errors', $originalDisplayErrors);
            ini_set('display_startup_errors', $originalStartupErrors);
            mb_internal_encoding($originalEncoding);
            unlink($statFile);
            Talon::reset();
        }
    }

    public function testInitEnvironmentSetsIniLocaleAndMbstringDefaults(): void
    {
        Talon::reset();
        $settings = Settings::fromArray(['root' => dirname(__DIR__, 3)]);

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

    public function testInitEnvironmentTunesXdebugIniWhenLoaded(): void
    {
        Talon::reset();
        $settings = Settings::fromArray(['root' => dirname(__DIR__, 3)]);

        // isExtensionLoaded() is faked here because the test suite doesn't
        // reliably run with the xdebug extension loaded (coverage uses pcov),
        // so this branch would otherwise never execute.
        $runner = new XdebugForcedRunner($settings);

        $result = $runner->boot();

        $this->assertSame($settings, $result);

        if (extension_loaded('xdebug')) {
            $this->assertSame('1', ini_get('xdebug.cli_color'));
            $this->assertSame('On', ini_get('xdebug.dump_globals'));
            $this->assertSame('On', ini_get('xdebug.show_local_vars'));
            $this->assertSame('100', ini_get('xdebug.max_nesting_level'));
            $this->assertSame('4', ini_get('xdebug.var_display_max_depth'));
        }
    }
}
